<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Event\Poll\AnswerEvent;
use Civix\CoreBundle\Event\Poll\QuestionEvent;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Service\CommentManager;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Service\Settings;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeaderContentSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var PetitionManager
     */
    private $petitionManager;
    /**
     * @var \Civix\CoreBundle\Service\CommentManager
     */
    private $commentManager;

    public static function getSubscribedEvents()
    {
        return [
            // petition
            UserPetitionEvents::PETITION_CREATE => [
                ['addPetitionHashTags'],
                ['addPetitionRootComment'],
                ['subscribePetitionAuthor'],
            ],
            UserPetitionEvents::PETITION_UPDATE => 'addPetitionHashTags',
            // post
            PostEvents::POST_PRE_CREATE => 'setPostExpire',
            PostEvents::POST_CREATE => [
                ['addPostHashTags'],
                ['addPostRootComment'],
                ['subscribePostAuthor'],
            ],
            PostEvents::POST_UPDATE => 'addPostHashTags',
            // poll
            PollEvents::QUESTION_PUBLISHED => [
                ['addQuestionHashTags'],
                ['setQuestionExpire'],
            ],
            PollEvents::QUESTION_CREATE => [
                ['subscribePollAuthor'],
            ],
            PollEvents::QUESTION_ANSWER => [
                ['updateCrowdfundingPledgedAmount'],
                ['addCommentByQuestionAnswer'],
                ['setVisibleAnswersForRecipient'],
                ['updateResponsesQuestion'],
            ],
        ];
    }

    public function __construct(
        EntityManager $em, 
        Settings $settings,
        PetitionManager $petitionManager,
        CommentManager $commentManager
    ) {
        $this->em = $em;
        $this->settings = $settings;
        $this->petitionManager = $petitionManager;
        $this->commentManager = $commentManager;
    }

    public function setPostExpire(PostEvent $event)
    {
        $post = $event->getPost();
        $key = 'micropetition_expire_interval_'.$post->getGroup()->getGroupType();
        $interval = $this->settings->get($key)->getValue();
        $post->setExpiredAt(new \DateTime("+$interval days"));
        $post->setUserExpireInterval($interval);
    }

    public function setQuestionExpire(QuestionEvent $event)
    {
        $expireInterval = $this->settings->get(Settings::POLL_EXPIRE_INTERVAL)->getValue();
        $event->getQuestion()->setExpireAt(new \DateTime("+$expireInterval days"));
    }

    public function addPetitionHashTags(UserPetitionEvent $event)
    {
        $this->em->getRepository('CivixCoreBundle:HashTag')
            ->addForPetition($event->getPetition());
    }

    public function addPostHashTags(PostEvent $event)
    {
        $this->em->getRepository('CivixCoreBundle:HashTag')
            ->addForPost($event->getPost());
    }

    public function addQuestionHashTags(QuestionEvent $event)
    {
        $this->em->getRepository('CivixCoreBundle:HashTag')
            ->addForQuestion($event->getQuestion());
    }

    public function addPetitionRootComment(UserPetitionEvent $event)
    {
        $this->commentManager->addUserPetitionRootComment($event->getPetition());
    }

    public function addPostRootComment(PostEvent $event)
    {
        $this->commentManager->addPostRootComment($event->getPost());
    }

    public function subscribePetitionAuthor(UserPetitionEvent $event)
    {
        $petition = $event->getPetition();
        $author = $petition->getUser();
        $author->addPetitionSubscription($petition);
        $this->em->persist($author);
        $this->em->flush();
    }

    public function subscribePostAuthor(PostEvent $event)
    {
        $post = $event->getPost();
        $author = $post->getUser();
        $author->addPostSubscription($post);
        $this->em->persist($author);
        $this->em->flush();
    }

    public function subscribePollAuthor(QuestionEvent $event)
    {
        $poll = $event->getQuestion();
        $author = $poll->getUser();
        $author->addPollSubscription($poll);
        $this->em->persist($author);
        $this->em->flush();
    }

    public function updateCrowdfundingPledgedAmount(AnswerEvent $event)
    {
        $answer = $event->getAnswer();
        $question = $answer->getQuestion();

        if ($question instanceof PaymentRequest && $question->getIsCrowdfunding() &&
            $answer->getCurrentPaymentAmount()) {
            $this->em->getRepository(PaymentRequest::class)->updateCrowdfundingPledgedAmount($answer);
        }
    }

    public function addCommentByQuestionAnswer(AnswerEvent $event)
    {
        $this->commentManager->addCommentByQuestionAnswer($event->getAnswer());
    }

    public function setVisibleAnswersForRecipient(AnswerEvent $event)
    {
        $answer = $event->getAnswer();
        $question = $answer->getQuestion();

        $specRepresentative = $question->getReportRecipient();
        $offTitleGroup = $question->getReportRecipientGroup();
        //Add specific representative to recipients of this question
        if (isset($specRepresentative)) {
            $question->addRecipient($specRepresentative);
        } elseif (isset($offTitleGroup)) {
            //check if user has representative with recient official title
            $districts = $answer->getUser()->getDistrictsIds();

            //check if user has districts (fill profile info)
            if (!empty($districts)) {
                $representatives = $this->em->getRepository(Representative::class)
                    ->getReprByDistrictsAndOffTitle($districts, $offTitleGroup);

                //check if base has representatives with selected official title and districts
                if ($representatives) {
                    foreach ($representatives as $recipient) {
                        if ($question->getOwner() != $recipient) {
                            $question->addRecipient($recipient);
                        }
                    }
                }
            }
        }

        $this->em->persist($question);
        $this->em->flush();
    }

    public function updateResponsesQuestion(AnswerEvent $event)
    {
        $question = $event->getAnswer()->getQuestion();
        if ($question instanceof LeaderNews) {
            $this->em->getRepository('CivixCoreBundle:Activity')
                ->updateLeaderNewsResponseCountQuestion($question);
        } else {
            $this->em->getRepository('CivixCoreBundle:Poll\Question')
                ->updateAnswersCount($question);
            $this->em->getRepository('CivixCoreBundle:Activity')
                ->updateResponseCountQuestion($question);
        }
    }
}
<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\HashTag;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Event\Poll\AnswerEvent;
use Civix\CoreBundle\Event\Poll\QuestionEvent;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Service\CommentManager;
use Civix\CoreBundle\Service\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeaderContentSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var \Civix\CoreBundle\Service\CommentManager
     */
    private $commentManager;

    public static function getSubscribedEvents(): array
    {
        return [
            // petition
            UserPetitionEvents::PETITION_PRE_CREATE => [
                ['setPetitionExpire'],
                ['setPetitionFacebookThumbnailImageName'],
            ],
            UserPetitionEvents::PETITION_CREATE => [
                ['addPetitionHashTags'],
                ['subscribePetitionAuthor'],
            ],
            UserPetitionEvents::PETITION_UPDATE => 'addPetitionHashTags',
            // post
            PostEvents::POST_PRE_CREATE => [
                ['setPostExpire'],
                ['setPostFacebookThumbnailImageName'],
            ],
            PostEvents::POST_CREATE => [
                ['addPostHashTags'],
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
            ],
            PollEvents::QUESTION_PRE_CREATE => 'checkHasPayoutAccount'
        ];
    }

    public function __construct(
        EntityManagerInterface $em,
        Settings $settings,
        CommentManager $commentManager
    ) {
        $this->em = $em;
        $this->settings = $settings;
        $this->commentManager = $commentManager;
    }

    public function setPostExpire(PostEvent $event)
    {
        $post = $event->getPost();
        $group = $post->getGroup();
        if ($group) {
            $key = 'micropetition_expire_interval_'.$group->getGroupType();
            $interval = (int)$this->settings->get($key)->getValue();
            $post->setExpiredAt(new \DateTime("+$interval days"));
            $post->setUserExpireInterval($interval);
        }
    }

    public function setPetitionExpire(UserPetitionEvent $event)
    {
        $petition = $event->getPetition();
        $group = $petition->getGroup();
        if ($group) {
            $key = 'micropetition_expire_interval_'.$group->getGroupType();
            $interval = (int)$this->settings->get($key)->getValue();
            $petition->setExpiredAt(new \DateTime("+$interval days"));
        }
    }

    public function setPostFacebookThumbnailImageName(PostEvent $event)
    {
        $event->getPost()->getFacebookThumbnail()->setName(bin2hex(random_bytes(10)).'.png');
    }

    public function setPetitionFacebookThumbnailImageName(UserPetitionEvent $event)
    {
        $event->getPetition()->getFacebookThumbnail()->setName(bin2hex(random_bytes(10)).'.png');
    }

    public function setQuestionExpire(QuestionEvent $event)
    {
        $expireInterval = $this->settings->get(Settings::POLL_EXPIRE_INTERVAL)->getValue();
        $event->getQuestion()->setExpireAt(new \DateTime("+$expireInterval days"));
    }

    public function addPetitionHashTags(UserPetitionEvent $event)
    {
        $this->em->getRepository(HashTag::class)
            ->addForTaggableEntity($event->getPetition());
    }

    public function addPostHashTags(PostEvent $event)
    {
        $this->em->getRepository(HashTag::class)
            ->addForTaggableEntity($event->getPost());
    }

    public function addQuestionHashTags(QuestionEvent $event)
    {
        $this->em->getRepository(HashTag::class)
            ->addForTaggableEntity($event->getQuestion());
    }

    public function subscribePetitionAuthor(UserPetitionEvent $event)
    {
        $petition = $event->getPetition();
        $author = $petition->getUser();
        if ($author) {
            $author->addPetitionSubscription($petition);
            $this->em->flush();
        }
    }

    public function subscribePostAuthor(PostEvent $event)
    {
        $post = $event->getPost();
        $author = $post->getUser();
        if ($author) {
            $author->addPostSubscription($post);
            $this->em->flush();
        }
    }

    public function subscribePollAuthor(QuestionEvent $event)
    {
        $poll = $event->getQuestion();
        $author = $poll->getUser();
        $author->addPollSubscription($poll);
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

    public function updateResponsesQuestion(AnswerEvent $event)
    {
        $question = $event->getAnswer()->getQuestion();
        $this->em->getRepository('CivixCoreBundle:Poll\Question')
            ->updateAnswersCount($question);
        $this->em->getRepository('CivixCoreBundle:Activity')
            ->updateResponseCountQuestion($question);
    }

    public function checkHasPayoutAccount(QuestionEvent $event)
    {
        $poll = $event->getQuestion();

        if (!$poll instanceof PaymentRequest || $poll->getIsCrowdfunding()) {
            return;
        }
        $account = $poll->getOwner()->getStripeAccount();
        if (!$account || !count($account->getBankAccounts())) {
            throw new \RuntimeException('You must have a Stripe account to create a payment request.');
        }
    }
}
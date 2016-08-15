<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Event\Poll\QuestionEvent;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Service\Poll\CommentManager;
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
     * @var CommentManager
     */
    private $commentManager;

    public static function getSubscribedEvents()
    {
        return [
            UserPetitionEvents::PETITION_CREATE => [
                ['addPetitionHashTags'],
                ['addPetitionRootComment'],
            ],
            UserPetitionEvents::PETITION_UPDATE => 'addPetitionHashTags',

            PostEvents::POST_PRE_CREATE => 'setPostExpire',
            PostEvents::POST_CREATE => [
                ['addPostHashTags'],
                ['addPostRootComment'],
            ],
            PostEvents::POST_UPDATE => 'addPostHashTags',

            PollEvents::QUESTION_PUBLISHED => [
                ['addQuestionHashTags'],
                ['setQuestionExpire'],
            ]
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
}
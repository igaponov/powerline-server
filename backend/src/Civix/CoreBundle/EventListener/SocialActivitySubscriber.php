<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event;
use Civix\CoreBundle\Service\SocialActivityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SocialActivitySubscriber implements EventSubscriberInterface
{
    /**
     * @var SocialActivityManager
     */
    private $manager;

    public function __construct(SocialActivityManager $manager)
    {
        $this->manager = $manager;
    }

    public static function getSubscribedEvents()
    {
        return [
            Event\UserEvents::FOLLOWED => 'sendUserFollowRequest',
            Event\GroupEvents::PERMISSIONS_CHANGED => 'noticeGroupsPermissionsChanged',
            Event\UserPetitionEvents::PETITION_CREATE => 'noticeUserPetitionCreated',
            Event\PostEvents::POST_CREATE => 'noticePostCreated',
        ];
    }

    public function sendUserFollowRequest(Event\UserFollowEvent $event)
    {
        $this->manager->sendUserFollowRequest($event->getUserFollow());
    }

    public function noticeGroupsPermissionsChanged(Event\GroupEvent $event)
    {
        $this->manager->noticeGroupsPermissionsChanged($event->getGroup());
    }

    public function noticeUserPetitionCreated(Event\UserPetitionEvent $event)
    {
        $this->manager->noticeUserPetitionCreated($event->getPetition());
    }

    public function noticePostCreated(Event\PostEvent $event)
    {
        $this->manager->noticePostCreated($event->getPost());
    }
}
<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
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
            UserEvents::FOLLOWED => 'sendUserFollowRequest',
        ];
    }

    public function sendUserFollowRequest(UserFollowEvent $event)
    {
        $this->manager->sendUserFollowRequest($event->getUserFollow());
    }
}
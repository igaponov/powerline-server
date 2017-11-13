<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\UserLocalGroupManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserLocalGroupSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserLocalGroupManager
     */
    private $manager;

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::REGISTRATION => 'joinLocalGroups',
            UserEvents::ADDRESS_CHANGE => 'joinLocalGroups',
        ];
    }

    public function __construct(UserLocalGroupManager $manager)
    {
        $this->manager = $manager;
    }

    public function joinLocalGroups(UserEvent $event): void
    {
        $this->manager->joinLocalGroups($event->getUser());
    }
}
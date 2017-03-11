<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::FOLLOW_REQUEST_APPROVE => 'updateUserReport',
            UserEvents::UNFOLLOW => 'updateUserReport',
            UserEvents::REGISTRATION => 'createUserReport',
        ];
    }

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function updateUserReport(UserFollowEvent $event)
    {
        $user = $event->getUserFollow()->getUser();
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($user, $user->getFollowers()->count());
    }

    public function createUserReport(UserEvent $event)
    {
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($event->getUser(), 0);
    }
}
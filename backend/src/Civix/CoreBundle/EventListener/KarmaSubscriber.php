<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Event;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KarmaSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var EntityRepository
     */
    private $repository;

    public static function getSubscribedEvents()
    {
        return [
            Event\UserEvents::VIEW_REPRESENTATIVES => 'viewRepresentatives',
            Event\UserEvents::FOLLOW => 'follow',
            Event\UserEvents::FOLLOW_REQUEST_APPROVE => 'approveFollowRequest',
            Event\GroupEvents::USER_JOINED => 'joinGroup',
        ];
    }

    public function __construct(EntityManagerInterface $em, EntityRepository $repository)
    {
        $this->em = $em;
        $this->repository = $repository;
    }

    public function viewRepresentatives(Event\UserRepresentativeEvent $event)
    {
        $user = $event->getUser();
        $karma = $this->repository
            ->findOneBy(['user' => $user, 'type' => Karma::TYPE_VIEW_ANNOUNCEMENT]);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_VIEW_ANNOUNCEMENT, 25);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function follow(Event\UserFollowEvent $event)
    {
        $userFollow = $event->getUserFollow();
        $user = $userFollow->getFollower();
        $karma = $this->repository->findOneBy(['user' => $user, 'type' => Karma::TYPE_FOLLOW]);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_FOLLOW, 10, ['following_id' => $userFollow->getUser()->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function approveFollowRequest(Event\UserFollowEvent $event)
    {
        $userFollow = $event->getUserFollow();
        $user = $userFollow->getUser();
        $karma = $this->repository
            ->findOneBy(['user' => $user, 'type' => Karma::TYPE_APPROVE_FOLLOW_REQUEST]);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_APPROVE_FOLLOW_REQUEST, 10, ['follower_id' => $userFollow->getFollower()->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function joinGroup(Event\GroupUserEvent $event)
    {
        $user = $event->getUser();
        $group = $event->getGroup();
        $karma = $this->repository
            ->findOneBy(['user' => $user, 'type' => Karma::TYPE_JOIN_GROUP]);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_JOIN_GROUP, 10, ['group_id' => $group->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }
}
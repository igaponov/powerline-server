<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\Event\UserRepresentativeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KarmaSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::VIEW_REPRESENTATIVES => 'viewRepresentatives',
            UserEvents::FOLLOW => 'follow',
        ];
    }

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function viewRepresentatives(UserRepresentativeEvent $event)
    {
        $user = $event->getUser();
        $karma = $this->em->getRepository(Karma::class)->findOneBy(['user' => $user, 'type' => Karma::TYPE_VIEW_ANNOUNCEMENT]);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_VIEW_ANNOUNCEMENT, 25);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }

    public function follow(UserFollowEvent $event)
    {
        $userFollow = $event->getUserFollow();
        $user = $userFollow->getFollower();
        $karma = $this->em->getRepository(Karma::class)->findOneBy(['user' => $user, 'type' => Karma::TYPE_FOLLOW]);
        if (!$karma) {
            $karma = new Karma($user, Karma::TYPE_FOLLOW, 10, ['following_id' => $userFollow->getUser()->getId()]);
            $this->em->persist($karma);
            $this->em->flush();
        }
    }
}
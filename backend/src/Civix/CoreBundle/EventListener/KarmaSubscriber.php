<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Event\UserEvents;
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
}
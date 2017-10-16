<?php

namespace Civix\CoreBundle\Service\Representative;

use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\UserRepresentativeEvent;
use Civix\CoreBundle\Event\UserRepresentativeEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RepresentativeManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    public function save(UserRepresentative $representative): UserRepresentative
    {
        $event = new AvatarEvent($representative);
        $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

        $this->entityManager->persist($representative);
        $this->entityManager->flush();

        return $representative;
    }

    public function approveRepresentative(UserRepresentative $representative): UserRepresentative
    {
        $representative->setStatus(UserRepresentative::STATUS_ACTIVE);

        $event = new UserRepresentativeEvent($representative);
        $this->dispatcher->dispatch(UserRepresentativeEvents::APPROVE, $event);

        return $representative;
    }
}

<?php

namespace Civix\CoreBundle\Service\Representative;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\RepresentativeEvent;
use Civix\CoreBundle\Event\RepresentativeEvents;
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

    public function save(Representative $representative): Representative
    {
        $event = new AvatarEvent($representative);
        $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

        $this->entityManager->persist($representative);
        $this->entityManager->flush();

        return $representative;
    }

    public function approveRepresentative(Representative $representative): Representative
    {
        $representative->setStatus(Representative::STATUS_ACTIVE);

        $event = new RepresentativeEvent($representative);
        $this->dispatcher->dispatch(RepresentativeEvents::APPROVE, $event);

        return $representative;
    }
}

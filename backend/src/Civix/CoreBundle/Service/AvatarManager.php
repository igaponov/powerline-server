<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\HasAvatarInterface;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AvatarManager
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EntityManager $em, EventDispatcherInterface $dispatcher)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function deleteAvatar(HasAvatarInterface $entity)
    {
        $event = new AvatarEvent($entity);
        $this->dispatcher->dispatch(AvatarEvents::BEFORE_DELETE, $event);

        $entity->setAvatarFileName(null);

        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }
}
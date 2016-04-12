<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\PasswordEncodeInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class PasswordEncodeListener implements EventSubscriber
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $this->encodePassword($event);
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->encodePassword($event);
    }

    public function encodePassword(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        
        if (!$entity instanceof PasswordEncodeInterface) {
            return;
        }
        
        if ($password = $entity->getPlainPassword()) {
            $encoder = $this->encoderFactory->getEncoder($entity);
            $encodedPassword = $encoder->encodePassword($password, $entity->getSalt());
            $entity->setPassword($encodedPassword);
        }
    }
}
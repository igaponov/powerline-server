<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\AsyncEvent;
use Civix\CoreBundle\Service\RabbitMQCallback\EventMessage;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class AsyncEventListener
{
    /**
     * @var ProducerInterface
     */
    private $producer;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(ProducerInterface $producer, EntityManagerInterface $em)
    {
        $this->producer = $producer;
        $this->em = $em;
    }

    public function onAsyncEventDispatch(AsyncEvent $event): void
    {
        $originalEvent = $event->getEvent();
        // delete "async." prefix
        $message = new EventMessage(substr($event->getEventName(), 6), $originalEvent);
        $object = new \ReflectionObject($originalEvent);
        $properties = $object->getProperties();
        $metadataFactory = $this->em->getMetadataFactory();
        $proxyFactory = $this->em->getProxyFactory();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($originalEvent);
            $className = ltrim(get_class($value), '\\');
            if ($metadataFactory->hasMetadataFor($className)) {
                $class = $metadataFactory->getMetadataFor($className);
                $proxy = $proxyFactory->getProxy($className, $class->getIdentifierValues($value));
                $property->setValue($originalEvent, $proxy);
            }
            $property->setAccessible(false);
        }
        $this->producer->publish(serialize($message));
    }
}
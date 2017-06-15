<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\AsyncEvent;
use Civix\CoreBundle\Service\RabbitMQCallback\EventMessage;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class AsyncEventListener
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    public function onAsyncEventDispatch(AsyncEvent $event)
    {
        $message = new EventMessage(substr($event->getEventName(), 6), $event->getEvent());
        $this->producer->publish(serialize($message));
    }
}
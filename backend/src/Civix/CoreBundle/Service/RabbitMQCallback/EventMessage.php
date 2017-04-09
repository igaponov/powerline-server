<?php

namespace Civix\CoreBundle\Service\RabbitMQCallback;

use Symfony\Component\EventDispatcher\Event;

class EventMessage
{
    /**
     * @var string
     */
    private $eventName;
    /**
     * @var Event
     */
    private $event;

    public function __construct(string $eventName, Event $event)
    {
        $this->eventName = $eventName;
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
}
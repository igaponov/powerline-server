<?php

namespace Civix\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class AsyncEvent extends Event
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
<?php

namespace Civix\Component\Notification\Event;

use Civix\Component\Notification\Exception\NotificationException;
use Symfony\Component\EventDispatcher\Event;

class ErrorEvent extends Event
{
    /**
     * @var NotificationException
     */
    private $exception;

    public function __construct(NotificationException $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return NotificationException
     */
    public function getException(): NotificationException
    {
        return $this->exception;
    }
}
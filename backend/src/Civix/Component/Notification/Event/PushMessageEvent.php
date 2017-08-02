<?php

namespace Civix\Component\Notification\Event;

use Civix\Component\Notification\PushMessage;
use Symfony\Component\EventDispatcher\Event;

class PushMessageEvent extends Event
{
    /**
     * @var PushMessage
     */
    private $message;

    public function __construct(PushMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return PushMessage
     */
    public function getMessage(): PushMessage
    {
        return $this->message;
    }
}
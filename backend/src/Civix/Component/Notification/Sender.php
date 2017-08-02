<?php

namespace Civix\Component\Notification;

use Civix\Component\Notification\Adapter\AdapterInterface;
use Civix\Component\Notification\Event\PushMessageEvent;
use Civix\Component\Notification\Event\PushMessageEvents;
use Civix\Component\Notification\Handler\HandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Sender
{
    /**
     * @var AdapterInterface
     */
    private $adapter;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var HandlerInterface
     */
    private $handler;

    public function __construct(
        AdapterInterface $adapter,
        HandlerInterface $handler,
        EventDispatcherInterface $dispatcher
    ) {
        $this->adapter = $adapter;
        $this->handler = $handler;
        $this->dispatcher = $dispatcher;
    }

    public function send(PushMessage $message): void
    {
        $event = new PushMessageEvent($message);
        $this->dispatcher->dispatch(PushMessageEvents::PRE_SEND, $event);

        $this->adapter->send($message, $this->handler);
    }
}
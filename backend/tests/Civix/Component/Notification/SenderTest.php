<?php

namespace Tests\Civix\Component\Notification;

use Civix\Component\Notification\Adapter\AdapterInterface;
use Civix\Component\Notification\Event\PushMessageEvent;
use Civix\Component\Notification\Event\PushMessageEvents;
use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\PushMessage;
use Civix\Component\Notification\Sender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SenderTest extends TestCase
{
    public function testSend()
    {
        $recipient = $this->createMock(\Civix\Component\Notification\Model\RecipientInterface::class);
        $message = new PushMessage($recipient, '', '', '', [], '');
        $handler = $this->createMock(HandlerInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('send')
            ->with($message, $handler);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PushMessageEvents::PRE_SEND, $this->isInstanceOf(PushMessageEvent::class));
        $sender = new Sender($adapter, $handler, $dispatcher);
        $sender->send($message);
    }
}

<?php

namespace Tests\Civix\Component\Notification\Adapter;

use Civix\Component\Notification\Adapter\AdapterInterface;
use Civix\Component\Notification\Adapter\MultiAdapter;
use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\Model\RecipientInterface;
use Civix\Component\Notification\PushMessage;
use PHPUnit\Framework\TestCase;

class MultiAdapterTest extends TestCase
{
    public function testSend()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $message = new PushMessage($recipient, '', '', '');
        $mock = $this->createMock(AdapterInterface::class);
        $mock->expects($this->once())
            ->method('send')
            ->with($message);
        $adapter = new MultiAdapter($mock);
        $handler = $this->createMock(HandlerInterface::class);
        $adapter->send($message, $handler);
    }
}

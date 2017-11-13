<?php

namespace Tests\Civix\Component\Notification\Handler;

use Civix\Component\Notification\Event\ErrorEvent;
use Civix\Component\Notification\Event\PushMessageEvents;
use Civix\Component\Notification\Exception\NotificationException;
use Civix\Component\Notification\Handler\Handler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class HandlerTest extends TestCase
{
    public function testInvocation()
    {
        $data = ['x'];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Message pushed', $data);
        $handler = new Handler($dispatcher, $logger);
        $handler(null, $data);
    }

    public function testInvocationThrowsException()
    {
        $level = NotificationException::CODE_DEBUG;
        $exception = new NotificationException('Test', new \Exception(), $level);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PushMessageEvents::ERROR, $this->isInstanceOf(ErrorEvent::class));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with($level, $exception->getMessage(), ['exception' => $exception]);
        $handler = new Handler($dispatcher, $logger);
        $handler($exception);
    }
}
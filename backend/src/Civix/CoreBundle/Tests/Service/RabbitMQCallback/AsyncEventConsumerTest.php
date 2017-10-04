<?php

namespace Civix\CoreBundle\Tests\Service\RabbitMQCallback;

use Civix\CoreBundle\Service\RabbitMQCallback\AsyncEventConsumer;
use Civix\CoreBundle\Service\RabbitMQCallback\EventMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Proxy\ProxyFactory;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class AsyncEventConsumerTest extends TestCase
{
    public function testExecuteIsOk()
    {
        $eventName = 'event.name';
        $subject = $this->createMock(Proxy::class);
        $event = new GenericEvent($subject);
        $message = new EventMessage($eventName, $event);
        $msg = new AMQPMessage(serialize($message));
        $proxyFactory = $this->getMockBuilder(ProxyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['resetUninitializedProxy'])
            ->getMock();
        $proxyFactory->expects($this->once())
            ->method('resetUninitializedProxy')
            ->with($subject);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getProxyFactory')
            ->with()
            ->willReturn($proxyFactory);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($eventName, $event);
        $logger = $this->getLoggerMock();
        $logger->expects($this->never())
            ->method('critical');
        $consumer = new AsyncEventConsumer($em, $dispatcher, $logger);
        $this->assertTrue($consumer->execute($msg));
    }

    public function testInvalidMessageBody()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher->expects($this->never())
            ->method('dispatch');
        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
            ->method('critical');
        $consumer = new AsyncEventConsumer($em, $dispatcher, $logger);
        $this->assertTrue($consumer->execute(new AMQPMessage()));
    }

    public function testInvalidMessage()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher->expects($this->never())
            ->method('dispatch');
        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
            ->method('critical');
        $consumer = new AsyncEventConsumer($em, $dispatcher, $logger);
        $this->assertTrue($consumer->execute(new AMQPMessage('invalid body')));
    }

    public function testExecuteThrowsException()
    {
        $eventName = 'event.name';
        $event = new Event();
        $message = new EventMessage($eventName, $event);
        $msg = new AMQPMessage(serialize($message));
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($eventName, $event)
            ->willThrowException(new \Exception());
        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
            ->method('critical');
        $consumer = new AsyncEventConsumer($em, $dispatcher, $logger);
        $this->assertTrue($consumer->execute($msg));
    }

    public function testExecuteThrowsError()
    {
        $eventName = 'event.name';
        $event = new Event();
        $message = new EventMessage($eventName, $event);
        $msg = new AMQPMessage(serialize($message));
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($eventName, $event)
            ->willReturnCallback(function () {
                trigger_error('Parse error', E_PARSE);
            });
        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
            ->method('critical');
        $consumer = new AsyncEventConsumer($em, $dispatcher, $logger);
        $this->assertTrue($consumer->execute($msg));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    private function getDispatcherMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function getLoggerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createMock(LoggerInterface::class);
    }
}
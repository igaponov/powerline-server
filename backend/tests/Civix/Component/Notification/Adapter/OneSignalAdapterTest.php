<?php

namespace Tests\Civix\Component\Notification\Adapter;

use Civix\Component\Notification\Adapter\OneSignalAdapter;
use Civix\Component\Notification\DataFactory\DataFactoryInterface;
use Civix\Component\Notification\Exception\NotificationException;
use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\Model\RecipientInterface;
use Civix\Component\Notification\PushMessage;
use Civix\Component\Notification\Retriever\DeviceRetrieverInterface;
use OneSignal\Exception\OneSignalException;
use OneSignal\Notifications;
use PHPUnit\Framework\TestCase;

class OneSignalAdapterTest extends TestCase
{
    public function testSend()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $message = new PushMessage($recipient, '', '', '', [], '');
        $device = new Device($recipient);
        $callback = $this->callback(function ($data) {
            $this->assertInternalType('array', $data);

            return true;
        });
        $retriever = $this->createMock(DeviceRetrieverInterface::class);
        $retriever->expects($this->once())
            ->method('retrieve')
            ->with($recipient)
            ->willReturn([$device]);
        $notifications = $this->getNotificationsMock();
        $notifications->expects($this->once())
            ->method('add')
            ->with($callback);
        $factory = $this->createMock(DataFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createData')
            ->with($message, $device);
        $adapter = new OneSignalAdapter($retriever, $notifications, $factory);
        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(null, $this->isType('array'));
        $adapter->send($message, $handler);
    }

    public function testSendThrowsException()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $message = new PushMessage($recipient, '', '', '', [], '');
        $device = new Device($recipient);
        $retriever = $this->createMock(DeviceRetrieverInterface::class);
        $retriever->expects($this->once())
            ->method('retrieve')
            ->with($recipient)
            ->willReturn([$device]);
        $notifications = $this->getNotificationsMock();
        $exception = new OneSignalException('Test Error');
        $notifications->expects($this->once())
            ->method('add')
            ->with($this->callback(function ($data) {
                $this->assertInternalType('array', $data);

                return true;
            }))
            ->willThrowException($exception);
        $factory = $this->createMock(DataFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createData')
            ->with($message, $device);
        $adapter = new OneSignalAdapter($retriever, $notifications, $factory);
        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function (NotificationException $e) use ($exception) {
                $this->assertSame('Notification error: '.$exception->getMessage(), $e->getMessage());
                $this->assertSame($exception, $e->getPrevious());
                $this->assertSame(NotificationException::CODE_CRITICAL, $e->getLevel());

                return true;
            }), null);
        $adapter->send($message, $handler);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Notifications
     */
    private function getNotificationsMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(Notifications::class)
            ->disableOriginalConstructor()
            ->setMethods(['add'])
            ->getMock();
    }
}

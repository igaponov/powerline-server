<?php

namespace Tests\Civix\CoreBundle\Service\RabbitMQCallback;

use Civix\CoreBundle\Service\PushSender;
use Civix\CoreBundle\Service\RabbitMQCallback\PushQueue;
use Civix\CoreBundle\Service\Representative\RepresentativeManager;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PushQueueTest extends TestCase
{
    public function testExecuteWithException()
    {
        $msg = new AMQPMessage(serialize([
            'method' => 'sendSocialActivity',
            'params' => [1],
        ]));
        $sender = $this->getPushSenderMock();
        $e = new \Exception('Error');
        $sender->expects($this->once())
            ->method('sendSocialActivity')
            ->willThrowException($e);
        $manager = $this->getManagerMock();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Message handling failed.', ['message' => $msg, 'e' => $e]);
        $callback = new PushQueue([$sender, $manager], $logger);
        $this->assertTrue($callback->execute($msg));
    }

    public function testExecuteWithError()
    {
        $msg = new AMQPMessage(serialize([
            'method' => 'sendSocialActivity',
            'params' => [1],
        ]));
        $sender = $this->getPushSenderMock();
        $sender->expects($this->once())
            ->method('sendSocialActivity')
            ->willReturnCallback(function () {
                return (new \stdClass())->{'x'}();
            });
        $manager = $this->getManagerMock();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Message handling failed.', $this->callback(function ($context) use($msg) {
                $this->assertEquals($msg, $context['message']);
                /** @var \Throwable $error */
                $error = $context['e'];
                $this->assertInstanceOf(\Throwable::class, $error);
                $text = 'Call to undefined method stdClass::x()';
                $this->assertEquals($text, $error->getMessage());

                return true;
            }));
        $callback = new PushQueue([$sender, $manager], $logger);
        $this->assertTrue($callback->execute($msg));
    }

    public function testExecuteWithNoHandler()
    {
        $msg = new AMQPMessage(serialize([
            'method' => 'notExistentMethod',
        ]));
        $sender = $this->getPushSenderMock();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('No handler found for message.', ['message' => $msg]);
        $callback = new PushQueue([$sender], $logger);
        $this->assertTrue($callback->execute($msg));
    }

    /**
     * @return PushSender|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPushSenderMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        $sender = $this->getMockBuilder(PushSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $sender;
    }

    /**
     * @return RepresentativeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getManagerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $manager = $this->getMockBuilder(RepresentativeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
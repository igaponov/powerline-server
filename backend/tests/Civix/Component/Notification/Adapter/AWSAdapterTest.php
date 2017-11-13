<?php

namespace Tests\Civix\Component\Notification\Adapter;

use Aws\Command;
use Aws\Sns\Exception\SnsException;
use Aws\Sns\SnsClient;
use Civix\Component\Notification\Adapter\AWSAdapter;
use Civix\Component\Notification\DataFactory\DataFactoryInterface;
use Civix\Component\Notification\Exception\NotificationException;
use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\Model\AndroidEndpoint;
use Civix\Component\Notification\Model\RecipientInterface;
use Civix\Component\Notification\PushMessage;
use Civix\Component\Notification\Retriever\EndpointRetrieverInterface;
use PHPUnit\Framework\TestCase;

class AWSAdapterTest extends TestCase
{
    public function testSendWithAwsError()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $message = new PushMessage($recipient, '', '', '');
        $endpoint = new AndroidEndpoint();
        $endpoint->setToken('X-token')
            ->setArn('X-arn')
            ->setUser($recipient);
        $retriever = $this->createMock(EndpointRetrieverInterface::class);
        $retriever->expects($this->once())
            ->method('retrieve')
            ->with($recipient)
            ->willReturn([$endpoint]);
        $sns = $this->getSnsClientMock(['publish']);
        $exception = new SnsException('Error', new Command('Publish'), [
            'message' => 'AWS message'
        ]);
        $sns->expects($this->once())
            ->method('publish')
            ->willThrowException($exception);
        $factory = $this->createMock(DataFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createData')
            ->with($message, $endpoint);
        $service = new AWSAdapter($retriever, $sns, $factory);
        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function (NotificationException $e) use ($exception) {
                $this->assertSame('Notification error: '.$exception->getAwsErrorMessage(), $e->getMessage());
                $this->assertSame($exception, $e->getPrevious());
                $this->assertSame(NotificationException::CODE_CRITICAL, $e->getLevel());

                return true;
            }), null);
        $service->send($message, $handler);
    }

    public function testSendWithAwsEndpointDisabledError()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $message = new PushMessage($recipient, '', '', '');
        $endpoint = new AndroidEndpoint();
        $endpoint->setToken('X-token')
            ->setArn('X-arn')
            ->setUser($recipient);
        $retriever = $this->createMock(EndpointRetrieverInterface::class);
        $retriever->expects($this->once())
            ->method('retrieve')
            ->with($recipient)
            ->willReturn([$endpoint]);
        $sns = $this->getSnsClientMock(['publish', 'deleteEndpoint']);
        $exception = new SnsException('Error', new Command('Publish'), [
            'message' => 'AWS message',
            'code' => 'EndpointDisabled',
        ]);
        $sns->expects($this->once())
            ->method('publish')
            ->willThrowException($exception);
        $factory = $this->createMock(DataFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createData')
            ->with($message, $endpoint);
        $service = new AWSAdapter($retriever, $sns, $factory);
        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function (NotificationException $e) use ($exception) {
                $this->assertSame('Notification error: '.$exception->getAwsErrorMessage(), $e->getMessage());
                $this->assertSame($exception, $e->getPrevious());
                $this->assertSame(NotificationException::CODE_DEBUG, $e->getLevel());

                return true;
            }), null);
        $service->send($message, $handler);
    }

    public function testSend()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $message = new PushMessage($recipient, '', '', '');
        $endpoint = new AndroidEndpoint();
        $endpoint->setToken('X-token')
            ->setArn('X-arn')
            ->setUser($recipient);
        $retriever = $this->createMock(EndpointRetrieverInterface::class);
        $retriever->expects($this->once())
            ->method('retrieve')
            ->with($recipient)
            ->willReturn([$endpoint]);
        $sns = $this->getSnsClientMock(['publish']);
        $sns->expects($this->once())
            ->method('publish');
        $factory = $this->createMock(DataFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createData')
            ->with($message, $endpoint);
        $service = new AWSAdapter($retriever, $sns, $factory);
        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(null, $this->isType('array'));
        $service->send($message, $handler);
    }

    /**
     * @param array $methods
     * @return SnsClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getSnsClientMock(array $methods): \PHPUnit_Framework_MockObject_MockObject
    {
        $sns = $this->getMockBuilder(SnsClient::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();

        return $sns;
    }
}

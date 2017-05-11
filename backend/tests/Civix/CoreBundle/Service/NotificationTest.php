<?php

namespace Tests\Civix\CoreBundle\Service;

use Aws\Command;
use Aws\Sns\Exception\SnsException;
use Aws\Sns\SnsClient;
use Civix\CoreBundle\Entity\Notification\AndroidEndpoint;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\Notification;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationTest extends TestCase
{
    public function testPublishWithAwsError()
    {
        $user = new User();
        $user->setUsername('user1');
        $endpoint = new AndroidEndpoint();
        $endpoint->setToken('X-token')
            ->setArn('X-arn')
            ->setUser($user);
        $em = $this->getEntityManagerMock();
        $sns = $this->getSnsClientMock(['publish']);
        $exception = new SnsException('Error', new Command('Publish'), [
            'message' => 'AWS message'
        ]);
        $sns->expects($this->once())
            ->method('publish')
            ->willThrowException($exception);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('AWS message', [
                'token' => $endpoint->getToken(),
                'arn' => $endpoint->getArn(),
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                ]
            ]);
        $service = new Notification($em, $sns, '', '', $logger);
        $service->send('', '', '', [], '', $endpoint);
    }

    public function testPublishWithAwsEndpointDisabledError()
    {
        $user = new User();
        $user->setUsername('user1');
        $endpoint = new AndroidEndpoint();
        $endpoint->setToken('X-token')
            ->setArn('X-arn')
            ->setUser($user);
        $em = $this->getEntityManagerMock();
        $em->expects($this->once())
            ->method('remove')
            ->with($endpoint);
        $em->expects($this->once())
            ->method('flush')
            ->with($endpoint);
        $sns = $this->getSnsClientMock(['publish', 'deleteEndpoint']);
        $exception = new SnsException('Error', new Command('Publish'), [
            'message' => 'AWS message',
            'code' => 'EndpointDisabled',
        ]);
        $sns->expects($this->once())
            ->method('publish')
            ->willThrowException($exception);
        $sns->expects($this->once())
            ->method('deleteEndpoint')
            ->with([
                'EndpointArn' => $endpoint->getArn(),
            ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('AWS message', [
                'token' => $endpoint->getToken(),
                'arn' => $endpoint->getArn(),
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                ]
            ]);
        $service = new Notification($em, $sns, '', '', $logger);
        $service->send('', '', '', [], '', $endpoint);
    }

    /**
     * @return EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getEntityManagerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $em;
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

<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\EventListener\AMQPSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Exception\StopConsumerException;
use PHPUnit\Framework\TestCase;

class AMQPSubscriberTest extends TestCase
{
    public function testOnAfterProcessingMessage()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('isOpen')
            ->with()
            ->willReturn(false);
        $subscriber = new AMQPSubscriber($em);
        $this->expectException(StopConsumerException::class);
        $subscriber->onAfterProcessingMessage();
    }
}

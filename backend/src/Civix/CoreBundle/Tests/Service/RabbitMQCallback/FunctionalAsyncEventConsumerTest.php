<?php

namespace Civix\CoreBundle\Tests\Service\RabbitMQCallback;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Service\RabbitMQCallback\AsyncEventConsumer;
use Civix\CoreBundle\Service\RabbitMQCallback\EventMessage;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Doctrine\ORM\Proxy\Proxy;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FunctionalAsyncEventConsumerTest extends WebTestCase
{
    public function testDeserialization()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        /** @var Post|Proxy $post */
        $post = $repository->getReference('post_1');
        $this->containers = [];
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('event_name', $this->callback(function (PostEvent $event) {
                $entity = $event->getPost();
                $this->assertNotNull($entity->getUser());

                return true;
            }));
        $logger = $this->createMock(LoggerInterface::class);
        $consumer = new AsyncEventConsumer($em, $dispatcher, $logger);
        $event = new PostEvent($post);
        $message = new EventMessage('event_name', $event);
        $msg = new AMQPMessage(serialize($message));
        $consumer->execute($msg);
    }
}
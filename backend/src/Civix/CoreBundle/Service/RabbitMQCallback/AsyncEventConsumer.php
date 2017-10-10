<?php

namespace Civix\CoreBundle\Service\RabbitMQCallback;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AsyncEventConsumer implements ConsumerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     */
    public function execute(AMQPMessage $msg): bool
    {
        $this->logger->debug('Handle async event.', ['message' => $msg]);
        $message = @unserialize($msg->getBody());
        if (!$message instanceof EventMessage) {
            $this->logger->critical('Wrong message for async event queue.', ['message' => $msg]);
            return true;
        }
        $originalEvent = $message->getEvent();
        try {
            $this->resetUninitializedProxies($originalEvent);
            $this->dispatcher->dispatch($message->getEventName(), $originalEvent);
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage(), ['message' => $message, 'exception' => $e]);
        }

        return true;
    }

    private function resetUninitializedProxies(Event $originalEvent)
    {
        $object = new \ReflectionObject($originalEvent);
        $properties = $object->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($originalEvent);
            if ($value instanceof Proxy) {
                $value = $this->em->merge($value);
                $property->setValue($originalEvent, $value);
            }
            $property->setAccessible(false);
        }
    }
}
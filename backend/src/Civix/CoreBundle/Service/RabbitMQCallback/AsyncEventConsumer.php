<?php

namespace Civix\CoreBundle\Service\RabbitMQCallback;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AsyncEventConsumer implements ConsumerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     */
    public function execute(AMQPMessage $msg)
    {
        $this->logger->debug('Handle async event.', ['message' => $msg]);
        $message = @unserialize($msg->getBody());
        if (!$message instanceof EventMessage) {
            $this->logger->critical('Wrong message for async event queue.', ['message' => $msg]);
            return true;
        }
        try {
            $this->dispatcher->dispatch($message->getEventName(), $message->getEvent());
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage(), ['message' => $msg]);
        }

        return true;
    }
}
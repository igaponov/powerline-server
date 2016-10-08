<?php

namespace Civix\CoreBundle\Service;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class QueueTask implements QueueTaskInterface
{
    /**
     * @var ProducerInterface
     */
    protected $rabbitMQ;

    public function __construct(ProducerInterface $rabbitMq)
    {
        $this->rabbitMQ = $rabbitMq;
    }

    public function addToQueue($class, $method, $params)
    {
        $message = array(
            'class' => $class,
            'method' => $method,
            'params' => $params,
        );

        $this->addMessageToQueue($message);
    }

    public function addMessageToQueue($message)
    {
        $this->rabbitMQ->publish(serialize($message));
    }
}

<?php

namespace Civix\CoreBundle\Service;

class QueueTask
{
    protected $rabbitMQ;

    public function __construct($rabbitMq)
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

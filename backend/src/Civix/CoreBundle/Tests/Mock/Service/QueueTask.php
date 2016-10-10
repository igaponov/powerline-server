<?php

namespace Civix\CoreBundle\Tests\Mock\Service;

use Civix\CoreBundle\Service\QueueTaskInterface;

class QueueTask implements QueueTaskInterface
{
    /**
     * @var \SplQueue
     */
    private $queue;

    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    public function addToQueue($method, $params, $class = 'MockQueueTask')
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
        $this->queue->enqueue($message);
    }

    public function count()
    {
        return $this->queue->count();
    }

    public function hasMessageWithMethod($method, $params = null)
    {
        return array_reduce(iterator_to_array($this->queue), function ($count, $item) use ($method, $params) {
            if ($item['method'] === $method && ($params === null || $item['params'] === $params)) {
                $count++;
            }

            return $count;
        }, 0);
    }
}

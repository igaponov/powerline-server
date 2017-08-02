<?php

namespace Civix\Component\Notification\Adapter;

use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\PushMessage;

class MultiAdapter implements AdapterInterface
{
    /**
     * @var AdapterInterface[]
     */
    private $adapters;

    public function __construct(AdapterInterface ...$adapters)
    {
        $this->adapters = $adapters;
    }

    public function send(PushMessage $message, HandlerInterface $callback): void
    {
        foreach ($this->adapters as $adapter) {
            $adapter->send($message, $callback);
        }
    }
}
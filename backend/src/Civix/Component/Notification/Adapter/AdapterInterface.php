<?php

namespace Civix\Component\Notification\Adapter;

use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\PushMessage;

interface AdapterInterface
{
    public function send(PushMessage $message, HandlerInterface $callback): void;
}
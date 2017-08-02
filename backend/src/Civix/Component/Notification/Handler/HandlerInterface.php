<?php

namespace Civix\Component\Notification\Handler;

use Civix\Component\Notification\Exception\NotificationException;

interface HandlerInterface
{
    public function __invoke(?NotificationException $e, array $data = null);
}
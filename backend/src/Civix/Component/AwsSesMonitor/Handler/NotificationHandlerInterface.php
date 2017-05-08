<?php

namespace Civix\Component\AwsSesMonitor\Handler;

interface NotificationHandlerInterface
{
    public function handle(string $message);
}
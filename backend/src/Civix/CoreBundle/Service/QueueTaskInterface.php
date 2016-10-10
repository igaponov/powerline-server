<?php
namespace Civix\CoreBundle\Service;

interface QueueTaskInterface
{
    public function addToQueue($class, $method, $params);

    public function addMessageToQueue($message);
}
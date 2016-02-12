<?php

namespace Civix\CoreBundle\Service;

class PushTask extends QueueTask
{
    public function addToQueue($method, $params, $class = 'Civix\CoreBundle\Service\PushSender')
    {
        parent::addToQueue($class, $method, $params);
    }
}

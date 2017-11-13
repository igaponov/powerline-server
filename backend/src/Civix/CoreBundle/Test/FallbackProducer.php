<?php

namespace Civix\CoreBundle\Test;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class FallbackProducer implements ProducerInterface
{
    private $basicPublishLog = array();

    public function publish($msgBody, $routingKey = '', $additionalProperties = []): void
    {
        $this->basicPublishLog[] = [
            'msg' => $msgBody,
            'routing_key' => $routingKey,
            'properties' => $additionalProperties,
        ];
    }

    public function getBasicPublishLog(): array
    {
        return $this->basicPublishLog;
    }
}
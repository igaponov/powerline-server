<?php

namespace Civix\CoreBundle\DataCollector;

use Civix\CoreBundle\Test\FallbackProducer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class RabbitMQDataCollector extends DataCollector
{
    /**
     * @var ProducerInterface[]
     */
    private $producers;

    public function __construct(ProducerInterface ...$producers)
    {
        $this->producers = $producers;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
        foreach ($this->producers as $producer) {
            if ($producer instanceof FallbackProducer) {
                foreach ($producer->getBasicPublishLog() as $log) {
                    $this->data[] = $log;
                }
            }
        }
    }

    public function getName(): string
    {
        return 'rabbit_mq';
    }

    public function getData(): array
    {
        return $this->data;
    }
}
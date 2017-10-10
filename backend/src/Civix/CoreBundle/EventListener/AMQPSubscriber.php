<?php

namespace Civix\CoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use OldSound\RabbitMqBundle\RabbitMq\Exception\StopConsumerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AMQPSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public static function getSubscribedEvents(): array
    {
        return [
            AfterProcessingMessageEvent::NAME => 'onAfterProcessingMessage',
        ];
    }

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Stop consuming if Entity manager was stopped after an error
     *
     * @throws \OldSound\RabbitMqBundle\RabbitMq\Exception\StopConsumerException
     */
    public function onAfterProcessingMessage()
    {
        if (!$this->em->isOpen()) {
            throw new StopConsumerException('Entity manager was stopped.');
        }
    }
}
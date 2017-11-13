<?php

namespace Civix\CoreBundle\Service\RabbitMQCallback;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class AsyncEventConsumer implements ConsumerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var VarCloner
     */
    private $cloner;
    /**
     * @var CliDumper
     */
    private $dumper;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->cloner = new VarCloner();
        $this->cloner->setMaxItems(-1);
        $this->dumper = new CliDumper(null, null);
        $this->dumper->setColors(false);
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     * @throws \Exception
     */
    public function execute(AMQPMessage $msg): bool
    {
        $message = @unserialize($msg->getBody());
        if (!$message instanceof EventMessage) {
            $this->logger->critical('Wrong message for async event queue.', [
                'message' => $msg,
            ]);
            return true;
        }
        $clone = $this->cloner->cloneVar($message)->withRefHandles(false);
        $this->logger->debug('Handle async event.', [
            'message' => $this->dumper->dump($clone, true),
        ]);
        $originalEvent = $message->getEvent();
        try {
            $this->resetUninitializedProxies($originalEvent);
            $this->dispatcher->dispatch($message->getEventName(), $originalEvent);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage(), [
                'message' => $this->dumper->dump($clone, true),
                'exception' => $e,
            ]);
        } finally {
            $this->em->clear();
        }

        return true;
    }

    private function resetUninitializedProxies(Event $originalEvent)
    {
        $object = new \ReflectionObject($originalEvent);
        $properties = $object->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($originalEvent);
            if ($value instanceof Proxy) {
                $value = $this->em->merge($value);
                $property->setValue($originalEvent, $value);
            }
            $property->setAccessible(false);
        }
    }
}
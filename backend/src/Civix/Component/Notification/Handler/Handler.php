<?php

namespace Civix\Component\Notification\Handler;

use Civix\Component\Notification\Event\ErrorEvent;
use Civix\Component\Notification\Event\PushMessageEvents;
use Civix\Component\Notification\Exception\NotificationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Handler implements HandlerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var HandlerInterface
     */
    private $handler;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        HandlerInterface $handler = null
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->handler = $handler;
    }

    public function __invoke(?NotificationException $e, array $data = null)
    {
        if ($e !== null) {
            $event = new ErrorEvent($e);
            $this->dispatcher->dispatch(PushMessageEvents::ERROR, $event);
            $this->logger->log($e->getLevel(), $e->getMessage(), ['exception' => $e]);
        } else {
            $this->logger->debug('Message pushed', $data);
        }
        if ($this->handler) {
            call_user_func($this->handler, $e, $data);
        }
    }
}
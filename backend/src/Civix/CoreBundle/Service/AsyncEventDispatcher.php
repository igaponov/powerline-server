<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Event\AsyncEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AsyncEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Add a wrapper for async event
     * to dispatch the specific event listener
     *
     * @param string $eventName
     * @param Event|null $event
     * @return AsyncEvent|Event
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (strpos($eventName, 'async.') === 0) {
            $event = new AsyncEvent($eventName, $event);
            $eventName = 'async_event.dispatch';
        }

        return $this->dispatcher->dispatch($eventName, $event);
    }

    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function removeListener($eventName, $listener)
    {
        $this->dispatcher->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->removeSubscriber($subscriber);
    }

    public function getListeners($eventName = null)
    {
        return $this->dispatcher->getListeners($eventName);
    }

    public function getListenerPriority($eventName, $listener)
    {
        return $this->dispatcher->getListenerPriority($eventName, $listener);
    }

    public function hasListeners($eventName = null)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Proxies all method calls to the original event dispatcher.
     *
     * @param string $method    The method name
     * @param array  $arguments The method arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->dispatcher, $method), $arguments);
    }
}
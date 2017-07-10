<?php

namespace Tests\Civix\CoreBundle\Event;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EventsTestCase extends KernelTestCase
{
    public function assertListeners($eventName, $eventClass, $expectedListeners): void
    {
        $kernel = self::createKernel();
        $kernel->boot();
        $container = $kernel->getContainer();
        $dispatcher = $container->get('event_dispatcher');
        $listeners = $dispatcher->getListeners($eventName);
        $this->assertCount(count($expectedListeners), $listeners);
        foreach ($listeners as $key => $listener) {
            $expectedListener = $expectedListeners[$key];
            if (is_array($expectedListener)) {
                $this->assertInstanceOf($expectedListener[0], $listener[0]);
                $this->assertSame($expectedListener[1], $listener[1]);
                $class = new \ReflectionClass($listener[0]);
                $function = $class->getMethod($listener[1]);
            } elseif ($expectedListener instanceof \Closure || is_string($expectedListener)) {
                $function = new \ReflectionFunction($expectedListener);
            } else {
                throw new \PHPUnit_Framework_AssertionFailedError(
                    'Invalid listener: '.serialize($expectedListener)
                );
            }
            $parameter = $function->getParameters()[0];
            $this->assertSame($eventClass, $parameter->getClass()->getName());
        }
    }
}
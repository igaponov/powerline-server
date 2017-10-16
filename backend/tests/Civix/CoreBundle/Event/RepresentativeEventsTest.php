<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\CoreBundle\Event\RepresentativeEvent;
use Civix\CoreBundle\Event\RepresentativeEvents;
use Civix\CoreBundle\EventListener\CongressSubscriber;
use Civix\CoreBundle\EventListener\OpenstatesSubscriber;
use Civix\CoreBundle\EventListener\ProPublicaSubscriber;

class RepresentativeEventsTest extends EventsTestCase
{
    public function testRegistrationEvent(): void
    {
        $expectedListeners = [
            [OpenstatesSubscriber::class, 'updateRepresentativeProfile'],
            [CongressSubscriber::class, 'updateRepresentativeProfile'],
            [ProPublicaSubscriber::class, 'getInfo'],
        ];
        $this->assertListeners(
            RepresentativeEvents::UPDATE,
            RepresentativeEvent::class,
            $expectedListeners
        );
    }
}

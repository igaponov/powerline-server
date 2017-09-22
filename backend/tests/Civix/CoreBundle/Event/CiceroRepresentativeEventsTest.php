<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\CoreBundle\Event\CiceroRepresentativeEvent;
use Civix\CoreBundle\Event\CiceroRepresentativeEvents;
use Civix\CoreBundle\EventListener\CongressSubscriber;
use Civix\CoreBundle\EventListener\OpenstatesSubscriber;
use Civix\CoreBundle\EventListener\ProPublicaSubscriber;

class CiceroRepresentativeEventsTest extends EventsTestCase
{
    public function testRegistrationEvent(): void
    {
        $expectedListeners = [
            [OpenstatesSubscriber::class, 'updateRepresentativeProfile'],
            [CongressSubscriber::class, 'updateRepresentativeProfile'],
            [ProPublicaSubscriber::class, 'getInfo'],
        ];
        $this->assertListeners(
            CiceroRepresentativeEvents::UPDATE,
            CiceroRepresentativeEvent::class,
            $expectedListeners
        );
    }
}

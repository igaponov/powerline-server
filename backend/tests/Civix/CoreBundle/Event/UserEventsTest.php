<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\EventListener\CiceroSubscriber;
use Civix\CoreBundle\EventListener\DiscountCodeSubscriber;
use Civix\CoreBundle\EventListener\GeocoderSubscriber;
use Civix\CoreBundle\EventListener\MailerSubscriber;
use Civix\CoreBundle\EventListener\ReportSubscriber;
use Civix\CoreBundle\EventListener\UserEventSubscriber;
use Civix\CoreBundle\EventListener\UserLocalGroupSubscriber;

class UserEventsTest extends EventsTestCase
{
    public function testRegistrationEvent(): void
    {
        $expectedListeners = [
            [DiscountCodeSubscriber::class, 'addDiscountCode'],
            [ReportSubscriber::class, 'createUserReport'],
            [GeocoderSubscriber::class, 'setCoordinates'],
            [UserLocalGroupSubscriber::class, 'joinLocalGroups'],
            [CiceroSubscriber::class, 'updateDistrictsIds'],
            [MailerSubscriber::class, 'sendRegistrationEmail'],
            [UserEventSubscriber::class, 'sendInviteFromGroup'],
        ];
        $this->assertListeners(UserEvents::REGISTRATION, UserEvent::class, $expectedListeners);
    }
}
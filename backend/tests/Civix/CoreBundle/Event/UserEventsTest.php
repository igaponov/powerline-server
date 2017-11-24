<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\EventListener\CiceroSubscriber;
use Civix\CoreBundle\EventListener\DiscountCodeSubscriber;
use Civix\CoreBundle\EventListener\GeocoderSubscriber;
use Civix\CoreBundle\EventListener\KarmaSubscriber;
use Civix\CoreBundle\EventListener\MailerSubscriber;
use Civix\CoreBundle\EventListener\ReportSubscriber;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;
use Civix\CoreBundle\EventListener\UserEventSubscriber;
use Civix\CoreBundle\EventListener\UserLocalGroupSubscriber;

class UserEventsTest extends EventsTestCase
{
    public function testLegacyRegistrationEvent(): void
    {
        $expectedListeners = [
            [UserLocalGroupSubscriber::class, 'joinLocalGroups'],
            [DiscountCodeSubscriber::class, 'addDiscountCode'],
            [ReportSubscriber::class, 'createUserReport'],
            [GeocoderSubscriber::class, 'setCoordinates'],
            [CiceroSubscriber::class, 'updateDistrictsIds'],
            [MailerSubscriber::class, 'sendRegistrationEmail'],
            [UserEventSubscriber::class, 'sendInviteFromGroup'],
        ];
        $this->assertListeners(UserEvents::LEGACY_REGISTRATION, UserEvent::class, $expectedListeners);
    }

    public function testRegistrationEvent(): void
    {
        $expectedListeners = [
            [UserLocalGroupSubscriber::class, 'joinLocalGroups'],
            [DiscountCodeSubscriber::class, 'addDiscountCode'],
            [ReportSubscriber::class, 'createUserReport'],
            [GeocoderSubscriber::class, 'setCoordinates'],
            [CiceroSubscriber::class, 'updateDistrictsIds'],
            [MailerSubscriber::class, 'sendRegistrationEmail'],
            [UserEventSubscriber::class, 'sendInviteFromGroup'],
        ];
        $this->assertListeners(UserEvents::REGISTRATION, UserEvent::class, $expectedListeners);
    }

    public function testUnfollowEvent(): void
    {
        $expectedListeners = [
            [ReportSubscriber::class, 'updateUserReport'],
            [SocialActivitySubscriber::class, 'deleteUserFollowRequest'],
        ];
        $this->assertListeners(UserEvents::UNFOLLOW, UserFollowEvent::class, $expectedListeners);
    }

    public function testFollowRequestApproveEvent(): void
    {
        $expectedListeners = [
            [ReportSubscriber::class, 'updateUserReport'],
            [KarmaSubscriber::class, 'approveFollowRequest'],
            [ReportSubscriber::class, 'updateKarmaApproveFollowRequest'],
            [SocialActivitySubscriber::class, 'deleteUserFollowRequest'],
        ];
        $this->assertListeners(UserEvents::FOLLOW_REQUEST_APPROVE, UserFollowEvent::class, $expectedListeners);
    }
}
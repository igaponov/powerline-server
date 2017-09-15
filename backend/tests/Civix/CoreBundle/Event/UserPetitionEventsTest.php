<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\ApiBundle\EventListener\LeaderContentSubscriber;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\EventListener\ActivityUpdateSubscriber;
use Civix\CoreBundle\EventListener\MentionSubscriber;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;

class UserPetitionEventsTest extends EventsTestCase
{
    public function testPreCreateEvent(): void
    {
        $expectedListeners = [
            [MentionSubscriber::class, 'onPetitionPreCreate'],
        ];
        $this->assertListeners(UserPetitionEvents::PETITION_PRE_CREATE, UserPetitionEvent::class, $expectedListeners);
    }

    public function testCreateEvent(): void
    {
        $expectedListeners = [
            [LeaderContentSubscriber::class, 'addPetitionHashTags'],
            [LeaderContentSubscriber::class, 'subscribePetitionAuthor'],
            [SocialActivitySubscriber::class, 'noticeUserPetitionCreated'],
            [ActivityUpdateSubscriber::class, 'publishUserPetitionToActivity'],
        ];
        $this->assertListeners(UserPetitionEvents::PETITION_CREATE, UserPetitionEvent::class, $expectedListeners);
    }
}
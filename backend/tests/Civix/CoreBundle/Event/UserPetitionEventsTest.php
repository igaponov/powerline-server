<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\ApiBundle\EventListener\LeaderContentSubscriber;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\EventListener\ActivityUpdateSubscriber;
use Civix\CoreBundle\EventListener\MentionSubscriber;
use Civix\CoreBundle\EventListener\MetadataSubscriber;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;
use Civix\CoreBundle\EventListener\ThumbnailSubscriber;

class UserPetitionEventsTest extends EventsTestCase
{
    public function testPreCreateEvent(): void
    {
        $expectedListeners = [
            [MentionSubscriber::class, 'onPetitionPreCreate'],
            [LeaderContentSubscriber::class, 'setPetitionFacebookThumbnailImageName'],
        ];
        $this->assertListeners(UserPetitionEvents::PETITION_PRE_CREATE, UserPetitionEvent::class, $expectedListeners);
    }

    public function testCreateEvent(): void
    {
        $expectedListeners = [
            [ThumbnailSubscriber::class, 'createPetitionFacebookThumbnail'],
            [MetadataSubscriber::class, 'handlePetition'],
            [LeaderContentSubscriber::class, 'addPetitionHashTags'],
            [LeaderContentSubscriber::class, 'subscribePetitionAuthor'],
            [SocialActivitySubscriber::class, 'noticeUserPetitionCreated'],
        ];
        $this->assertListeners(UserPetitionEvents::PETITION_CREATE, UserPetitionEvent::class, $expectedListeners);
    }
}
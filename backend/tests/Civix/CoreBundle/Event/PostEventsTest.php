<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\ApiBundle\EventListener\LeaderContentSubscriber;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\EventListener\ActivityUpdateSubscriber;
use Civix\CoreBundle\EventListener\KarmaSubscriber;
use Civix\CoreBundle\EventListener\ReportSubscriber;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;

class PostEventsTest extends EventsTestCase
{
    public function testCreateEvent(): void
    {
        $expectedListeners = [
            [KarmaSubscriber::class, 'createPost'],
            [LeaderContentSubscriber::class, 'addPostHashTags'],
            [LeaderContentSubscriber::class, 'subscribePostAuthor'],
            [ReportSubscriber::class, 'updateKarmaCreatePost'],
            [SocialActivitySubscriber::class, 'noticePostCreated'],
            [SocialActivitySubscriber::class, 'noticePostMentioned'],
            [ActivityUpdateSubscriber::class, 'publishPostToActivity'],
        ];
        $this->assertListeners(PostEvents::POST_CREATE, PostEvent::class, $expectedListeners);
    }
}
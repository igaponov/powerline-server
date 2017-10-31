<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\ApiBundle\EventListener\LeaderContentSubscriber;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\PostShareEvent;
use Civix\CoreBundle\EventListener\ActivityUpdateSubscriber;
use Civix\CoreBundle\EventListener\KarmaSubscriber;
use Civix\CoreBundle\EventListener\MentionSubscriber;
use Civix\CoreBundle\EventListener\MetadataSubscriber;
use Civix\CoreBundle\EventListener\PushSenderSubscriber;
use Civix\CoreBundle\EventListener\ReportSubscriber;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;
use Civix\CoreBundle\EventListener\ThumbnailSubscriber;

class PostEventsTest extends EventsTestCase
{
    public function testPreCreateEvent(): void
    {
        $expectedListeners = [
            [MentionSubscriber::class, 'onPostPreCreate'],
            [LeaderContentSubscriber::class, 'setPostExpire'],
            [LeaderContentSubscriber::class, 'setPostFacebookThumbnailImageName'],
        ];
        $this->assertListeners(PostEvents::POST_PRE_CREATE, PostEvent::class, $expectedListeners);
    }

    public function testCreateEvent(): void
    {
        $expectedListeners = [
            [KarmaSubscriber::class, 'createPost'],
            [ThumbnailSubscriber::class, 'createPostFacebookThumbnail'],
            [MetadataSubscriber::class, 'handlePost'],
            [LeaderContentSubscriber::class, 'addPostHashTags'],
            [LeaderContentSubscriber::class, 'subscribePostAuthor'],
            [ReportSubscriber::class, 'updateKarmaCreatePost'],
            [MentionSubscriber::class, 'onPostCreate'],
            [SocialActivitySubscriber::class, 'noticePostCreated'],
            [ActivityUpdateSubscriber::class, 'publishPostToActivity'],
        ];
        $this->assertListeners(PostEvents::POST_CREATE, PostEvent::class, $expectedListeners);
    }

    public function testUpdateEvent(): void
    {
        $expectedListeners = [
            [LeaderContentSubscriber::class, 'addPostHashTags'],
            [ActivityUpdateSubscriber::class, 'publishPostToActivity'],
        ];
        $this->assertListeners(PostEvents::POST_UPDATE, PostEvent::class, $expectedListeners);
    }

    public function testShareEvent()
    {
        $expectedListeners = [
            [PushSenderSubscriber::class, 'sendSharedPostPush'],
        ];
        $this->assertListeners(PostEvents::POST_SHARE, PostShareEvent::class, $expectedListeners);
    }
}
<?php

namespace Tests\Civix\CoreBundle\Event;

use Civix\CoreBundle\Event\CommentEvent;
use Civix\CoreBundle\Event\CommentEvents;
use Civix\CoreBundle\EventListener\ActivityUpdateSubscriber;
use Civix\CoreBundle\EventListener\MentionSubscriber;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;

class CommentEventsTest extends EventsTestCase
{
    public function testPreCreateEvent(): void
    {
        $expectedListeners = [
            [MentionSubscriber::class, 'handleCommentHtmlBody'],
        ];
        $this->assertListeners(CommentEvents::PRE_CREATE, CommentEvent::class, $expectedListeners);
    }

    public function testCreateEvent(): void
    {
        $expectedListeners = [
            [MentionSubscriber::class, 'onCommentCreate'],
            [SocialActivitySubscriber::class, 'noticeEntityCommented'],
            [ActivityUpdateSubscriber::class, 'updateResponsesComment'],
        ];
        $this->assertListeners(CommentEvents::CREATE, CommentEvent::class, $expectedListeners);
    }
}
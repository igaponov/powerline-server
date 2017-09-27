<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\PostShareEvent;
use Civix\CoreBundle\EventListener\PushSenderSubscriber;
use Civix\CoreBundle\Service\PushTask;
use PHPUnit\Framework\TestCase;

class PushSenderSubscriberTest extends TestCase
{
    public function testSendSharedPostPush()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Post $post */
        $post = $this->getMockBuilder(Post::class)
            ->setMethods(['getId'])
            ->getMock();
        $post->expects($this->once())
            ->method('getId')
            ->willReturn(8);
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $sharer */
        $sharer = $this->getMockBuilder(User::class)
            ->setMethods(['getId'])
            ->getMock();
        $sharer->expects($this->once())
            ->method('getId')
            ->willReturn(19);
        $event = new PostShareEvent($post, $sharer);
        $pushTask = $this->getMockBuilder(PushTask::class)
            ->disableOriginalConstructor()
            ->setMethods(['addToQueue'])
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|PushTask $pushTask */
        $pushTask->expects($this->once())
            ->method('addToQueue')
            ->with('sendSharedPostPush', [8, 19]);
        $subscriber = new PushSenderSubscriber($pushTask);
        $subscriber->sendSharedPostPush($event);
    }
}

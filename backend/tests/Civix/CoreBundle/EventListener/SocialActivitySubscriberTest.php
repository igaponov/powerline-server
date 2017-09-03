<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;
use Civix\CoreBundle\Service\SocialActivityManager;
use PHPUnit\Framework\TestCase;

class SocialActivitySubscriberTest extends TestCase
{
    public function testNoticePostCreated()
    {
        $post = new Post();
        $event = new PostEvent($post);
        $manager = $this->getSocialActivityManagerMock(['noticePostCreated']);
        $manager->expects($this->once())
            ->method('noticePostCreated')
            ->with($post);
        $subscriber = new SocialActivitySubscriber($manager);
        $subscriber->noticePostCreated($event);
    }

    public function testNoticeUserPetitionCreated()
    {
        $petition = new UserPetition();
        $event = new UserPetitionEvent($petition);
        $manager = $this->getSocialActivityManagerMock(['noticeUserPetitionCreated']);
        $manager->expects($this->once())
            ->method('noticeUserPetitionCreated')
            ->with($petition);
        $subscriber = new SocialActivitySubscriber($manager);
        $subscriber->noticeUserPetitionCreated($event);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|SocialActivityManager
     */
    private function getSocialActivityManagerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(SocialActivityManager::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}

<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\EventListener\ActivityUpdateSubscriber;
use Civix\CoreBundle\Service\ActivityUpdate;
use PHPUnit\Framework\TestCase;

class ActivityUpdateSubscriberTest extends TestCase
{
    public function testPublishPostToActivity()
    {
        $post = new Post();
        $event = new PostEvent($post);
        $activityUpdate = $this->getActivityUpdateMock(['publishPostToActivity']);
        $activityUpdate->expects($this->once())
            ->method('publishPostToActivity')
            ->with($post);
        $subscriber = new ActivityUpdateSubscriber($activityUpdate);
        $subscriber->publishPostToActivity($event);
    }

    public function testPublishUserPetitionToActivity()
    {
        $petition = new UserPetition();
        $event = new UserPetitionEvent($petition);
        $activityUpdate = $this->getActivityUpdateMock(['publishUserPetitionToActivity']);
        $activityUpdate->expects($this->once())
            ->method('publishUserPetitionToActivity')
            ->with($petition);
        $subscriber = new ActivityUpdateSubscriber($activityUpdate);
        $subscriber->publishUserPetitionToActivity($event);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|ActivityUpdate
     */
    private function getActivityUpdateMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(ActivityUpdate::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}

<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\EventListener\UserLocalGroupSubscriber;
use Civix\CoreBundle\Service\UserLocalGroupManager;
use PHPUnit\Framework\TestCase;

class UserLocalGroupSubscriberTest extends TestCase
{
    public function testJoinLocalGroups(): void
    {
        $user = new User();
        /** @var UserLocalGroupManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->getMockBuilder(UserLocalGroupManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['joinLocalGroups'])
            ->getMock();
        $manager->expects($this->once())
            ->method('joinLocalGroups')
            ->with($user);
        $subscriber = new UserLocalGroupSubscriber($manager);
        $event = new UserEvent($user);
        $subscriber->joinLocalGroups($event);
    }
}

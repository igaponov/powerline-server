<?php
namespace Civix\CoreBundle\Tests\EventListener;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\EventListener\PushSenderDoctrineSubscriber;
use Civix\CoreBundle\Service\PushTask;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

class PushSenderDoctrineSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testQueue()
    {
        $pushTask = $this->getMockBuilder(PushTask::class)
            ->disableOriginalConstructor()
            ->setMethods(['addToQueue'])
            ->getMock();
        $subscriber = new PushSenderDoctrineSubscriber($pushTask);
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $activity1 = new SocialActivity();
        $activity1->setId(1);
        $event = new LifecycleEventArgs($activity1, $em);
        $subscriber->postPersist($event);
        $user = new User();
        $event = new LifecycleEventArgs($user, $em);
        $subscriber->postPersist($event);
        $activity2 = new SocialActivity();
        $activity2->setId(2);
        $event = new LifecycleEventArgs($activity2, $em);
        $subscriber->postPersist($event);
        $pushTask->expects($this->exactly(2))->method('addToQueue');
        $pushTask->expects($this->at(0))
            ->method('addToQueue')
            ->with(
                'sendSocialActivity',
                [$activity1->getId()]
            );
        $pushTask->expects($this->at(1))
            ->method('addToQueue')
            ->with(
                'sendSocialActivity',
                [$activity2->getId()]
            );
        $subscriber->postFlush();
        $subscriber->postFlush();
    }
}
<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\EventListener\ReportSubscriber;
use Civix\CoreBundle\Repository\Report\UserReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ReportSubscriberTest extends TestCase
{
    public function testCreateUserReport(): void
    {
        $user = new User();
        $repository = $this->getMockBuilder(UserReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['upsertUserReport'])
            ->getMock();
        $repository->expects($this->once())
            ->method('upsertUserReport')
            ->with($user, 0);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserReport::class)
            ->willReturn($repository);
        $subscriber = new ReportSubscriber($em);
        $event = new UserEvent($user);
        $subscriber->createUserReport($event);
    }
}

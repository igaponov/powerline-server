<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\PostEvent;
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

    public function testUpdateKarmaCreatePost(): void
    {
        $user = new User();
        $post = new Post();
        $post->setUser($user);
        $repository = $this->getMockBuilder(UserReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateUserReportKarma'])
            ->getMock();
        $repository->expects($this->once())
            ->method('updateUserReportKarma')
            ->with($user);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(UserReport::class)
            ->willReturn($repository);
        $subscriber = new ReportSubscriber($em);
        $event = new PostEvent($post);
        $subscriber->updateKarmaCreatePost($event);
    }
}

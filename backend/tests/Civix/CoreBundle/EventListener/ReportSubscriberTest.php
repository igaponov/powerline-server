<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\EventListener\ReportSubscriber;
use Civix\CoreBundle\Repository\Report\UserReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class ReportSubscriberTest extends TestCase
{
    public function testCreateUserReport(): void
    {
        $user = new User();
        $repository = $this->getUserReportRepositoryMock(['upsertUserReport']);
        $repository->expects($this->once())
            ->method('upsertUserReport')
            ->with($user, 0);
        $em = $this->getManagerMock(UserReport::class, $repository);
        $subscriber = new ReportSubscriber($em);
        $event = new UserEvent($user);
        $subscriber->createUserReport($event);
    }

    public function testUpdateUserReport(): void
    {
        $user = new User();
        $follower = new User();
        $userFollow = (new UserFollow())
            ->setUser($user)
            ->setFollower($follower);
        $user->addFollower($userFollow);
        $repository = $this->getUserReportRepositoryMock(['upsertUserReport']);
        $repository->expects($this->once())
            ->method('upsertUserReport')
            ->with($user, 1);
        $em = $this->getManagerMock(UserReport::class, $repository);
        $subscriber = new ReportSubscriber($em);
        $event = new UserFollowEvent($user, $follower);
        $subscriber->updateUserReport($event);
    }

    public function testUpdateKarmaCreatePost(): void
    {
        $user = new User();
        $post = new Post();
        $post->setUser($user);
        $repository = $this->getUserReportRepositoryMock(['updateUserReportKarma']);
        $repository->expects($this->once())
            ->method('updateUserReportKarma')
            ->with($user);
        $em = $this->getManagerMock(UserReport::class, $repository);
        $subscriber = new ReportSubscriber($em);
        $event = new PostEvent($post);
        $subscriber->updateKarmaCreatePost($event);
    }

    public function testUpdateKarmaApproveFollowRequest(): void
    {
        $user = new User();
        $follower = new User();
        $repository = $this->getUserReportRepositoryMock(['updateUserReportKarma']);
        $repository->expects($this->once())
            ->method('updateUserReportKarma')
            ->with($user);
        $em = $this->getManagerMock(UserReport::class, $repository);
        $subscriber = new ReportSubscriber($em);
        $event = new UserFollowEvent($user, $follower);
        $subscriber->updateKarmaApproveFollowRequest($event);
    }

    /**
     * @param string $class
     * @param EntityRepository $repository
     * @return EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getManagerMock(string $class, EntityRepository $repository)
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($class)
            ->willReturn($repository);

        return $em;
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UserReportRepository
     */
    private function getUserReportRepositoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(UserReportRepository::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}

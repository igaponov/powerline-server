<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\EventListener\KarmaSubscriber;
use Civix\CoreBundle\Repository\KarmaRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class KarmaSubscriberTest extends TestCase
{
    public function testCreatePostFirstTime()
    {
        $user = new User();
        $post = new Post();
        $post->setUser($user);
        $type = Karma::TYPE_CREATE_POST;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Karma $karma) use ($user, $type) {
                $this->assertSame($user, $karma->getUser());
                $this->assertSame($type, $karma->getType());
                $this->assertSame(10, $karma->getPoints());
                $this->assertSame(['post_id' => null], $karma->getMetadata());

                return true;
            }));
        $em->expects($this->once())
            ->method('flush')
            ->with();
        $repository = $this->getKarmaRepositoryMock(['findOneByUserAndType']);
        $repository->expects($this->once())
            ->method('findOneByUserAndType')
            ->with($user, $type);
        $subscriber = new KarmaSubscriber($em, $repository);
        $event = new PostEvent($post);
        $subscriber->createPost($event);
    }

    public function testCreatePostSecondTime()
    {
        $user = new User();
        $post = new Post();
        $post->setUser($user);
        $type = Karma::TYPE_CREATE_POST;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('flush');
        $repository = $this->getKarmaRepositoryMock(['findOneByUserAndType']);
        $repository->expects($this->once())
            ->method('findOneByUserAndType')
            ->with($user, $type)
            ->willReturn(new Karma($user, $type, 10));
        $subscriber = new KarmaSubscriber($em, $repository);
        $event = new PostEvent($post);
        $subscriber->createPost($event);
    }

    public function testApproveFollowRequest()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $follower */
        $follower = $this->getMockBuilder(User::class)
            ->setMethods(['getId'])
            ->getMock();
        $follower->expects($this->once())
            ->method('getId')
            ->willReturn(67);
        $user = new User();
        $userFollow = (new UserFollow())
            ->setUser($user)
            ->setFollower($follower);
        $type = Karma::TYPE_APPROVE_FOLLOW_REQUEST;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Karma $karma) use ($user, $type) {
                $this->assertSame($user, $karma->getUser());
                $this->assertSame($type, $karma->getType());
                $this->assertSame(10, $karma->getPoints());
                $this->assertSame(['follower_id' => 67], $karma->getMetadata());

                return true;
            }));
        $em->expects($this->once())
            ->method('flush');
        $repository = $this->getKarmaRepositoryMock(['findOneByUserAndType']);
        $repository->expects($this->once())
            ->method('findOneByUserAndType')
            ->with($user, $type);
        $subscriber = new KarmaSubscriber($em, $repository);
        $event = new UserFollowEvent($userFollow);
        $subscriber->approveFollowRequest($event);
    }

    public function testApproveFollowRequestSecondTime()
    {
        $user = new User();
        $userFollow = new UserFollow();
        $userFollow->setUser($user);
        $type = Karma::TYPE_APPROVE_FOLLOW_REQUEST;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('flush');
        $repository = $this->getKarmaRepositoryMock(['findOneByUserAndType']);
        $repository->expects($this->once())
            ->method('findOneByUserAndType')
            ->with($user, $type)
            ->willReturn(new Karma($user, $type, 10));
        $subscriber = new KarmaSubscriber($em, $repository);
        $event = new UserFollowEvent($userFollow);
        $subscriber->approveFollowRequest($event);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|KarmaRepository
     */
    private function getKarmaRepositoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(KarmaRepository::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
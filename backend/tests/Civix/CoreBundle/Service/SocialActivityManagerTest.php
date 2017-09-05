<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\SocialActivityFactory;
use Civix\CoreBundle\Service\SocialActivityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SocialActivityManagerTest extends TestCase
{
    public function testNoticePostCreated()
    {
        $post = new Post();
        $manager = $this->getSocialActivityManager($post, 'createFollowPostCreatedActivity');
        $manager->noticePostCreated($post);
    }

    public function testNoticeUserPetitionCreated()
    {
        $petition = new UserPetition();
        $manager = $this->getSocialActivityManager($petition, 'createFollowUserPetitionCreatedActivity');
        $manager->noticeUserPetitionCreated($petition);
    }

    public function testNoticePostMentioned()
    {
        $activity = new SocialActivity();
        $recipient = new User();
        $user = new User();
        $group = new Group();
        $post = new Post();
        $post->setUser($user)
            ->setGroup($group);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($activity);
        $em->expects($this->once())
            ->method('flush')
            ->with();
        $repository = $this->getUserRepositoryMock(['filterByGroupAndFollower']);
        $repository->expects($this->once())
            ->method('filterByGroupAndFollower')
            ->with($group, $user, $recipient)
            ->willReturn([$recipient]);
        $factory = $this->getActivityFactoryMock(['createPostMentionedActivity']);
        $factory->expects($this->once())
            ->method('createPostMentionedActivity')
            ->with($post, $group, $recipient)
            ->willReturn($activity);
        $manager = new SocialActivityManager($em, $repository, $factory);
        $manager->noticePostMentioned($post, $recipient);
    }

    /**
     * @param BaseComment $comment
     * @param User $user
     * @param Group $group
     * @dataProvider getComments
     */
    public function testNoticeCommentMentioned(BaseComment $comment, User $user, Group $group)
    {
        $activity = new SocialActivity();
        $recipient = new User();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($activity);
        $em->expects($this->once())
            ->method('flush')
            ->with();
        $repository = $this->getUserRepositoryMock(['filterByGroupAndFollower']);
        $repository->expects($this->once())
            ->method('filterByGroupAndFollower')
            ->with($group, $user, $recipient)
            ->willReturn([$recipient]);
        $factory = $this->getActivityFactoryMock(['createCommentMentionedActivity']);
        $factory->expects($this->once())
            ->method('createCommentMentionedActivity')
            ->with($comment, $group, $recipient)
            ->willReturn($activity);
        $manager = new SocialActivityManager($em, $repository, $factory);
        $manager->noticeCommentMentioned($comment, $recipient);
    }

    public function getComments()
    {
        $user = new User();
        $group = new Group();

        return [
            [(new Post\Comment($user))->setPost((new Post())->setGroup($group)), $user, $group],
            [(new Poll\Comment($user))->setQuestion((new Poll\Question\GroupPetition())->setOwner($group)), $user, $group],
            [(new Post\Comment($user))->setPost((new Post())->setGroup($group)), $user, $group],
        ];
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UserRepository
     */
    private function getUserRepositoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|SocialActivityFactory
     */
    private function getActivityFactoryMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(SocialActivityFactory::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param Post|UserPetition $entity
     * @param string $method
     * @return SocialActivityManager
     */
    private function getSocialActivityManager($entity, string $method): SocialActivityManager
    {
        $activity = new SocialActivity();
        $user = new User();
        $group = new Group();
        $entity->setUser($user)
            ->setGroup($group);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($activity);
        $em->expects($this->once())
            ->method('flush')
            ->with();
        $repository = $this->getUserRepositoryMock();
        $factory = $this->getActivityFactoryMock();
        $factory->expects($this->once())
            ->method($method)
            ->with($entity)
            ->willReturn($activity);

        return new SocialActivityManager($em, $repository, $factory);
    }
}

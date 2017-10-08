<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Repository\SocialActivityRepository;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\SocialActivityFactory;
use Civix\CoreBundle\Service\SocialActivityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator;
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
     * @param Post|UserPetition|BaseComment $entity
     * @param string $method
     * @return SocialActivityManager
     */
    private function getSocialActivityManager($entity, string $method): SocialActivityManager
    {
        $activity = new SocialActivity();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($activity);
        $repository = $this->getUserRepositoryMock();
        $factory = $this->getActivityFactoryMock();
        $factory->expects($this->once())
            ->method($method)
            ->with($entity)
            ->willReturn($activity);

        return new SocialActivityManager($em, $repository, $factory);
    }

    public function testNoticePollCommented()
    {
        $question = (new Poll\Question\Group())->setOwner(new Group());
        $comment = (new Poll\Comment(new User()))->setQuestion($question);
        $manager = $this->getSocialActivityManager($comment, 'createFollowPollCommentedActivity');
        $manager->noticePollCommented($comment);
    }

    public function testNoticePollCommentReplied()
    {
        $comment = new Poll\Comment(new User(), new Poll\Comment(new User()));
        $manager = $this->getSocialActivityManager($comment, 'createPollCommentRepliedActivity');
        $manager->noticePollCommentReplied($comment);
    }

    public function testNoticeOwnPollCommented()
    {
        $user = (new User())->setUsername('-');
        $question = (new Poll\Question\Group())->setUser($user)->addSubscriber($user);
        $comment = (new Poll\Comment(new User()))->setQuestion($question);
        $manager = $this->getSocialActivityManager($comment, 'createOwnPollCommentedActivity');
        $manager->noticeOwnPollCommented($comment);
    }

    public function testNoticeUserPetitionCommented()
    {
        $petition = (new UserPetition())->setGroup(new Group());
        $comment = (new UserPetition\Comment(new User()))->setPetition($petition);
        $manager = $this->getSocialActivityManager($comment, 'createFollowUserPetitionCommentedActivity');
        $manager->noticeUserPetitionCommented($comment);
    }

    public function testNoticeUserPetitionCommentReplied()
    {
        $comment = new UserPetition\Comment(new User(), new UserPetition\Comment(new User()));
        $manager = $this->getSocialActivityManager($comment, 'createUserPetitionCommentRepliedActivity');
        $manager->noticeUserPetitionCommentReplied($comment);
    }

    public function testNoticeOwnUserPetitionCommented()
    {
        $user = (new User())->setUsername('-');
        $question = (new UserPetition())->setUser($user)->addSubscriber($user);
        $comment = (new UserPetition\Comment(new User()))->setPetition($question);
        $manager = $this->getSocialActivityManager($comment, 'createOwnUserPetitionCommentedActivity');
        $manager->noticeOwnUserPetitionCommented($comment);
    }

    public function testNoticePostCommented()
    {
        $post = (new Post())->setGroup(new Group());
        $comment = (new Post\Comment(new User()))->setPost($post);
        $manager = $this->getSocialActivityManager($comment, 'createFollowPostCommentedActivity');
        $manager->noticePostCommented($comment);
    }

    public function testNoticePostCommentReplied()
    {
        $comment = new Post\Comment(new User(), new Post\Comment(new User()));
        $manager = $this->getSocialActivityManager($comment, 'createPostCommentRepliedActivity');
        $manager->noticePostCommentReplied($comment);
    }

    public function testNoticeOwnPostCommented()
    {
        $user = (new User())->setUsername('-');
        $question = (new Post())->setUser($user)->addSubscriber($user);
        $comment = (new Post\Comment(new User()))->setPost($question);
        $manager = $this->getSocialActivityManager($comment, 'createOwnPostCommentedActivity');
        $manager->noticeOwnPostCommented($comment);
    }

    public function testDeleteUserFollowActivity()
    {
        $generator = function (User $user, $followerId) {
            /** @var \PHPUnit_Framework_MockObject_MockObject|User $follower */
            $follower = $this->getMockBuilder(User::class)
                ->setMethods(['getId'])
                ->getMock();
            $follower->expects($this->any())
                ->method('getId')
                ->willReturn($followerId);
            $userFollow = (new UserFollow())
                ->setUser($user)
                ->setFollower($follower);

            return $userFollow;
        };
        $activityFactory = new SocialActivityFactory();
        $user = new User();
        $activities = [
            [$activityFactory->createFollowRequestActivity($generator($user, 6))],
            [$activityFactory->createFollowRequestActivity($userFollow = $generator($user, 19))],
            [$activityFactory->createFollowRequestActivity($generator($user, 34))],
        ];
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var \PHPUnit_Framework_MockObject_MockObject|SimpleObjectHydrator $hydrator */
        $hydrator = $this->getMockBuilder(SimpleObjectHydrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['hydrateRow'])
            ->getMock();
        $hydrator->expects($this->exactly(2))
            ->method('hydrateRow')
            ->willReturnOnConsecutiveCalls(...$activities);
        $repository = $this->getMockBuilder(SocialActivityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByRecipientAndType'])
            ->getMock();
        $repository->expects($this->once())
            ->method('findByRecipientAndType')
            ->with($user, SocialActivity::TYPE_FOLLOW_REQUEST)
            ->willReturn(new IterableResult($hydrator));
        $em->expects($this->once())
            ->method('getRepository')
            ->with(SocialActivity::class)
            ->willReturn($repository);
        $em->expects($this->once())
            ->method('remove')
            ->with($activities[1][0]);
        $em->expects($this->once())
            ->method('flush');
        $manager = new SocialActivityManager($em, $this->getUserRepositoryMock(), $activityFactory);
        $manager->deleteUserFollowActivity($userFollow);
    }
}

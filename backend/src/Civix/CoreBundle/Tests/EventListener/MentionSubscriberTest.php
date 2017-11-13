<?php
namespace Civix\CoreBundle\Tests\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroupManager;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\CommentEvent;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\EventListener\MentionSubscriber;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\SocialActivityManager;
use PHPUnit\Framework\TestCase;

class MentionSubscriberTest extends TestCase
{
    /**
     * @param array $usernames
     * @param string $body
     * @param string $expected
     * @dataProvider getDataForPreCreate
     */
    public function testPostPreCreate($usernames, $body, $expected)
    {
        $entity = new Post();
        $event = new PostEvent($entity);
        $entity->setBody($body);
        $subscriber = $this->getMentionSubscriber($usernames);
        $subscriber->onPostPreCreate($event);
        $this->assertEquals($expected, $entity->getHtmlBody());
    }

    /**
     * @param array $usernames
     * @param string $body
     * @param string $expected
     * @dataProvider getDataForPreCreate
     */
    public function testUserPetitionPreCreate($usernames, $body, $expected)
    {
        $entity = new UserPetition();
        $event = new UserPetitionEvent($entity);
        $entity->setBody($body);
        $subscriber = $this->getMentionSubscriber($usernames);
        $subscriber->onPetitionPreCreate($event);
        $this->assertEquals($expected, $entity->getHtmlBody());
    }

    /**
     * @param array $usernames
     * @param string $body
     * @param string $expected
     * @dataProvider getDataForPreCreate
     */
    public function testHandleCommentHtmlBody($usernames, $body, $expected)
    {
        $entity = new Comment(new User());
        $event = new CommentEvent($entity);
        $entity->setCommentBody($body);
        $subscriber = $this->getMentionSubscriber($usernames);
        $subscriber->handleCommentHtmlBody($event);
        $this->assertEquals($expected, $entity->getCommentBodyHtml());
    }

    public function getDataForPreCreate()
    {
        $usernames = [1 => 'user1', 2 => 'user5', 3 => 'user17'];

        return [
            [
                $usernames,
                'Mention @everyone, @'.implode(' @', $usernames),
                'Mention @everyone, <a data-user-id="1">@user1</a> @user5 <a data-user-id="17">@user17</a>',
            ]
        ];
    }

    public function testOnCommentCreate()
    {
        $user = new User();
        $group = new Group();
        $group->addManager(new UserGroupManager($user, $group));
        $usernames = ['user1', 'user53', 'user97'];
        $comment = new Post\Comment($user);
        $comment->setCommentBody('mention @'.implode(' @', $usernames).'  @everyone');
        $post = new Post();
        $post->setGroup($group);
        $comment->setPost($post);
        $event = new CommentEvent($comment);
        $users = [new User(), new User(), new User()];
        $everyone = [new User(), new User()];
        $repository = $this->getUserRepositoryMock(['findBy', 'findAllMembersByGroup']);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['username' => $usernames])
            ->willReturn($users);
        $repository->expects($this->once())
            ->method('findAllMembersByGroup')
            ->with($group, ...$users)
            ->willReturn($everyone);
        $activityMock = $this->getActivityManagerMock(['noticeCommentMentioned']);
        $activityMock->expects($this->once())
            ->method('noticeCommentMentioned')
            ->with($comment, ...array_merge($users, $everyone));
        $subscriber = new MentionSubscriber($repository, $activityMock);
        $subscriber->onCommentCreate($event);
    }

    public function testOnPostCreate()
    {
        $usernames = ['user1', 'user53', 'user97'];
        $post = new Post();
        $post->setBody('mention @'.implode(' @', $usernames));
        $event = new PostEvent($post);
        $repository = $this->getUserRepositoryMock(['findBy']);
        $users = [new User(), new User(), new User()];
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['username' => $usernames])
            ->willReturn($users);
        $activityMock = $this->getActivityManagerMock(['noticePostMentioned']);
        $activityMock->expects($this->once())
            ->method('noticePostMentioned')
            ->with($post, ...$users);
        $subscriber = new MentionSubscriber($repository, $activityMock);
        $subscriber->onPostCreate($event);
    }

    private function getMentionSubscriber(array $usernames)
    {
        $user1 = $this->getUserMock(1, $usernames[1]);
        $user17 = $this->getUserMock(17, $usernames[3]);
        $repository = $this->getUserRepositoryMock(['findBy']);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['username' => $usernames])
            ->willReturn([$user1, $user17]);
        $activityManager = $this->getActivityManagerMock();

        return new MentionSubscriber($repository, $activityManager);
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
     * @param int $id
     * @param string $username
     * @return \PHPUnit_Framework_MockObject_MockObject|User
     */
    private function getUserMock(int $id, string $username): \PHPUnit_Framework_MockObject_MockObject
    {
        $mock = $this->getMockBuilder(User::class)
            ->setMethods(['getId', 'getUsername'])
            ->getMock();
        $mock->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $mock->expects($this->any())
            ->method('getUsername')
            ->willReturn($username);

        return $mock;
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|SocialActivityManager
     */
    private function getActivityManagerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(SocialActivityManager::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
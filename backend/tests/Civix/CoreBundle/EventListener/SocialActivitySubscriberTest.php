<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\CommentEvent;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\EventListener\SocialActivitySubscriber;
use Civix\CoreBundle\Service\SocialActivityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SocialActivitySubscriberTest extends TestCase
{
    public function testNoticePostCreated()
    {
        $post = new Post();
        $event = new PostEvent($post);
        $manager = $this->getSocialActivityManagerMock(['noticePostCreated']);
        $manager->expects($this->once())
            ->method('noticePostCreated')
            ->with($post);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('flush');
        $subscriber = new SocialActivitySubscriber($manager, $em);
        $subscriber->noticePostCreated($event);
    }

    public function testNoticeUserPetitionCreated()
    {
        $petition = new UserPetition();
        $event = new UserPetitionEvent($petition);
        $manager = $this->getSocialActivityManagerMock(['noticeUserPetitionCreated']);
        $manager->expects($this->once())
            ->method('noticeUserPetitionCreated')
            ->with($petition);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('flush');
        $subscriber = new SocialActivitySubscriber($manager, $em);
        $subscriber->noticeUserPetitionCreated($event);
    }

    /**
     * @param BaseComment $comment
     * @param string[] $methods
     * @dataProvider getComments
     */
    public function testNoticeEntityCommented(BaseComment $comment, string ...$methods)
    {
        $event = new CommentEvent($comment);
        $manager = $this->getSocialActivityManagerMock($methods);
        foreach ($methods as $method) {
            $manager->expects($this->once())
                ->method($method)
                ->with($comment);
        }
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('flush');
        $subscriber = new SocialActivitySubscriber($manager, $em);
        $subscriber->noticeEntityCommented($event);
    }

    public function getComments()
    {
        return [
            [
                new Poll\Comment(new User()),
                'noticePollCommented',
                'noticePollCommentReplied',
                'noticeOwnPollCommented',
            ],
            [
                new UserPetition\Comment(new User()),
                'noticeUserPetitionCommented',
                'noticeUserPetitionCommentReplied',
                'noticeOwnUserPetitionCommented',
            ],
            [
                new Post\Comment(new User()),
                'noticePostCommented',
                'noticePostCommentReplied',
                'noticeOwnPostCommented',
            ],
        ];
    }

    public function testDeleteUserFollowRequest()
    {
        $user = new User();
        $follower = new User();
        $event = new UserFollowEvent($user, $follower);
        $manager = $this->getSocialActivityManagerMock(['deleteUserFollowActivity']);
        $manager->expects($this->once())
            ->method('deleteUserFollowActivity')
            ->with($user, $follower);
        $em = $this->createMock(EntityManagerInterface::class);
        $subscriber = new SocialActivitySubscriber($manager, $em);
        $subscriber->deleteUserFollowRequest($event);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|SocialActivityManager
     */
    private function getSocialActivityManagerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(SocialActivityManager::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}

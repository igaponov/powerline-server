<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\CommentEvent;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\EventListener\ActivityUpdateSubscriber;
use Civix\CoreBundle\Service\ActivityUpdate;
use PHPUnit\Framework\TestCase;

class ActivityUpdateSubscriberTest extends TestCase
{
    public function testPublishPostToActivity()
    {
        $post = new Post();
        $event = new PostEvent($post);
        $activityUpdate = $this->getActivityUpdateMock(['publishPostToActivity']);
        $activityUpdate->expects($this->once())
            ->method('publishPostToActivity')
            ->with($post);
        $subscriber = new ActivityUpdateSubscriber($activityUpdate);
        $subscriber->publishPostToActivity($event);
    }

    public function testPublishUserPetitionToActivity()
    {
        $petition = new UserPetition();
        $event = new UserPetitionEvent($petition);
        $activityUpdate = $this->getActivityUpdateMock(['publishUserPetitionToActivity']);
        $activityUpdate->expects($this->once())
            ->method('publishUserPetitionToActivity')
            ->with($petition);
        $subscriber = new ActivityUpdateSubscriber($activityUpdate);
        $subscriber->publishUserPetitionToActivity($event);
    }

    /**
     * @param BaseComment $comment
     * @param string $method
     * @dataProvider getComments
     */
    public function testUpdateResponsesComment(BaseComment $comment, string $method)
    {
        $event = new CommentEvent($comment);
        $activityUpdate = $this->getActivityUpdateMock([$method]);
        $activityUpdate->expects($this->once())
            ->method($method)
            ->with($comment->getCommentedEntity());
        $subscriber = new ActivityUpdateSubscriber($activityUpdate);
        $subscriber->updateResponsesComment($event);
    }

    public function getComments()
    {
        $user = new User();

        return [
            [(new Poll\Comment($user))->setQuestion(new Poll\Question\Group()), 'updateResponsesQuestion'],
            [(new UserPetition\Comment($user))->setPetition(new UserPetition()), 'updateResponsesPetition'],
            [(new Post\Comment($user))->setPost(new Post()), 'updateResponsesPost'],
        ];
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|ActivityUpdate
     */
    private function getActivityUpdateMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(ActivityUpdate::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}

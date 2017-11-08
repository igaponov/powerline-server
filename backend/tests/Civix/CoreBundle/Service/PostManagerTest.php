<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\PostShareEvent;
use Civix\CoreBundle\Service\PostManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PostManagerTest extends TestCase
{
    public function testSharePost()
    {
        $sharer = new User();
        $vote = (new Post\Vote())->setUser($sharer)->setOption(Post\Vote::OPTION_UPVOTE);
        $post = (new Post())->addVote($vote);
        $this->assertNull($sharer->getLastContentSharedAt());
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PostEvents::POST_SHARE, $this->isInstanceOf(PostShareEvent::class));
        $manager = new PostManager($em, $dispatcher);
        $manager->sharePost($post, $sharer);
        $this->assertSame(date('Y-m-d H:i'), $sharer->getLastContentSharedAt()->format('Y-m-d H:i'));
    }

    public function testSharePostAfterLessThan72HoursThrowsException()
    {
        $sharer = (new User())->shareContent();
        $vote = (new Post\Vote())->setUser($sharer)->setOption(Post\Vote::OPTION_UPVOTE);
        $post = (new Post())->addVote($vote);
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $manager = new PostManager($em, $dispatcher);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User can share a post only once in 1 hour.');
        $manager->sharePost($post, $sharer);
    }

    public function testShareUnvotedPostThrowsException()
    {
        $sharer = new User();
        $post = new Post();
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $manager = new PostManager($em, $dispatcher);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User can share only a post he has upvoted.');
        $manager->sharePost($post, $sharer);
    }

    /**
     * @param $option
     * @dataProvider getOptions
     */
    public function testShareNonUpvotedPostThrowsException($option)
    {
        $sharer = new User();
        $vote = (new Post\Vote())->setUser($sharer)->setOption($option);
        $post = (new Post())->addVote($vote);
        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $manager = new PostManager($em, $dispatcher);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User can share only a post he has upvoted.');
        $manager->sharePost($post, $sharer);
    }

    public function getOptions()
    {
        return [[Post\Vote::OPTION_DOWNVOTE], [Post\Vote::OPTION_IGNORE]];
    }
}

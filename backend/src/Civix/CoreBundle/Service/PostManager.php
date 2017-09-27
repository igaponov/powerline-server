<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\Post\VoteEvent;
use Civix\CoreBundle\Event\PostShareEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PostManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Sign a petition with an answer
     *
     * @param Vote $vote
     * @return Vote
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function voteOnPost(Vote $vote): Vote
    {
        $this->entityManager->persist($vote);
        $this->entityManager->flush();

        $answerEvent = new VoteEvent($vote);
        $this->dispatcher->dispatch(PostEvents::POST_VOTE, $answerEvent);

        //check if need to publish to activity
        $post = $vote->getPost();
        if (!$post->isBoosted()
            && $this->checkIfNeedBoost($post)
            && $post->isAutomaticBoost()
        ) {
            $this->boostPost($post);
        }

        return $vote;
    }

    /**
     * Unsign a petition with an answer
     *
     * @param Vote $vote
     * @return Vote
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function unvotePost(Vote $vote): Vote
    {
        $event = new VoteEvent($vote);
        $this->dispatcher->dispatch(PostEvents::POST_PRE_UNVOTE, $event);

        $this->entityManager->remove($vote);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(PostEvents::POST_UNVOTE, $event);

        return $vote;
    }

    public function boostPost(Post $post): void
    {
        $post->boost();
        $this->entityManager->flush();

        $petitionEvent = new PostEvent($post);
        $this->dispatcher->dispatch(PostEvents::POST_BOOST, $petitionEvent);
    }

    /**
     * One user can create only 5 micropetition in one group per month.
     *
     * @param \Civix\CoreBundle\Entity\User  $user
     * @param \Civix\CoreBundle\Entity\Group $group
     *
     * @return bool
     */
    public function checkPostLimitPerMonth(User $user, Group $group): bool
    {
        $currentPetitionCount = $this->entityManager
            ->getRepository(Post::class)
            ->getCountPerMonthPostByOwner($user, $group);

        return $currentPetitionCount < $group->getPetitionPerMonth();
    }

    /**
     * Check count answers from group of petition. If it greater than 10% group's followers
     * than need to publish to activity.
     *
     * @param Post $post
     *
     * @return bool
     */
    public function checkIfNeedBoost(Post $post): bool
    {
        $groupAnswers = $this->entityManager->getRepository(Vote::class)
            ->getCountVoterFromGroup($post);

        return $groupAnswers >= $post->getQuorumCount();
    }

    /**
     * @param Post $post
     * @return Post
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function savePost(Post $post): Post
    {
        $isNew = !$post->getId();
        $event = new PostEvent($post);
        if ($isNew) {
            $this->dispatcher->dispatch(PostEvents::POST_PRE_CREATE, $event);
            $this->entityManager->persist($post);
        }

        $this->entityManager->flush();

        $eventName = $isNew ? PostEvents::POST_CREATE : PostEvents::POST_UPDATE;
        $this->dispatcher->dispatch('async.'.$eventName, $event);

        return $post;
    }

    public function sharePost(Post $post, User $sharer): void
    {
        $filter = function (Post\Vote $vote) use ($sharer) {
            return $vote->getUser()->isEqualTo($sharer) && $vote->isUpvote();
        };
        if ($post->getVotes()->filter($filter)->isEmpty()) {
            throw new \DomainException('User can share only a post he has upvoted.');
        }
        if ($sharer->getLastPostSharedAt() > new \DateTime('-72 hours')) {
            throw new \DomainException('User can share a post only once in 72 hours.');
        }

        $sharer->sharePost();

        $event = new PostShareEvent($post, $sharer);
        $this->dispatcher->dispatch(PostEvents::POST_SHARE, $event);
    }
}
<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\Post\VoteEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PostManager
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManager $entityManager,
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
     */
    public function voteOnPost(Vote $vote)
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
     */
    public function unvotePost(Vote $vote)
    {
        $event = new VoteEvent($vote);
        $this->dispatcher->dispatch(PostEvents::POST_PRE_UNVOTE, $event);

        $this->entityManager->remove($vote);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(PostEvents::POST_UNVOTE, $event);

        return $vote;
    }

    public function boostPost(Post $post)
    {
        $post->boost();
        $this->entityManager->persist($post);
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
    public function checkPostLimitPerMonth(User $user, Group $group)
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
    public function checkIfNeedBoost(Post $post)
    {
        $groupAnswers = $this->entityManager->getRepository(Vote::class)
            ->getCountVoterFromGroup($post);

        return $groupAnswers >= $post->getQuorumCount();
    }

    /**
     * @param Post $post
     * @return Post
     */
    public function savePost(Post $post)
    {
        $isNew = !$post->getId();
        $event = new PostEvent($post);
        if ($isNew) {
            $this->dispatcher->dispatch(PostEvents::POST_PRE_CREATE, $event);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $eventName = $isNew ? PostEvents::POST_CREATE : PostEvents::POST_UPDATE;
        $this->dispatcher->dispatch($eventName, $event);

        return $post;
    }
}
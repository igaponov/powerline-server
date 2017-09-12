<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SocialActivityManager
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var SocialActivityFactory
     */
    private $activityFactory;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $repository,
        SocialActivityFactory $activityFactory
    ) {
        $this->em = $em;
        $this->repository = $repository;
        $this->activityFactory = $activityFactory;
    }

    public function sendUserFollowRequest(UserFollow $follow): void
    {
        $socialActivity = $this->activityFactory->createFollowRequestActivity($follow);

        $this->em->persist($socialActivity);
        $this->em->flush();
    }

    public function noticeGroupJoiningApproved(User $user, Group $group): void
    {
        $socialActivity = $this->activityFactory->createJoinToGroupApprovedActivity($user, $group);

        $this->em->persist($socialActivity);
        $this->em->flush();
    }

    public function noticeUserPetitionCreated(UserPetition $petition): void
    {
        $socialActivity = $this->activityFactory->createFollowUserPetitionCreatedActivity($petition);
        $this->em->persist($socialActivity);
    }

    public function noticePostCreated(Post $post): void
    {
        $socialActivity = $this->activityFactory->createFollowPostCreatedActivity($post);
        $this->em->persist($socialActivity);
    }

    public function noticeAnsweredToQuestion(Answer $answer): void
    {
        $question = $answer->getQuestion();
        if (!$question->getOwner() instanceof Group) {
            return;
        }
        if ($question->getSubscribers()->contains($question->getUser())) {
            $socialActivity = $this->activityFactory->createOwnPollAnsweredActivity($answer);

            $this->em->persist($socialActivity);
            $this->em->flush();
        }
    }

    public function noticePollCommented(Poll\Comment $comment): void
    {
        $question = $comment->getQuestion();
        if ($question && $question->getOwner() instanceof Group) {
            $socialActivity = $this->activityFactory->createFollowPollCommentedActivity($comment);
            $this->em->persist($socialActivity);
        }
    }

    public function noticePollCommentReplied(Poll\Comment $comment): void
    {
        $parentComment = $comment->getParentComment();
        if ($parentComment && $parentComment->getUser()
            && $comment->getUser() !== $parentComment->getUser()) {
            $socialActivity = $this->activityFactory->createPollCommentRepliedActivity($comment);
            $this->em->persist($socialActivity);
        }
    }

    public function noticeOwnPollCommented(Poll\Comment $comment): void
    {
        $question = $comment->getQuestion();
        if ($question && $question->getUser()->getIsNotifOwnPostChanged()
            && $question->getSubscribers()->contains($question->getUser())
            && !$comment->getUser()->isEqualTo($question->getUser())
        ) {
            $socialActivity = $this->activityFactory->createOwnPollCommentedActivity($comment);
            $this->em->persist($socialActivity);
        }
    }

    public function noticeUserPetitionCommented(UserPetition\Comment $comment): void
    {
        $socialActivity = $this->activityFactory->createFollowUserPetitionCommentedActivity($comment);
        $this->em->persist($socialActivity);
    }

    public function noticeUserPetitionCommentReplied(UserPetition\Comment $comment): void
    {
        $parentComment = $comment->getParentComment();
        if ($parentComment && $parentComment->getUser() && $comment->getUser() !== $parentComment->getUser()) {
            $socialActivity2 = $this->activityFactory->createUserPetitionCommentRepliedActivity($comment);
            $this->em->persist($socialActivity2);
        }
    }

    public function noticeOwnUserPetitionCommented(UserPetition\Comment $comment): void
    {
        $petition = $comment->getPetition();
        $user = $petition->getUser();
        if ($user && $user->getIsNotifOwnPostChanged()
            && $petition->getSubscribers()->contains($user)
            && !$comment->getUser()->isEqualTo($user)
        ) {
            $socialActivity = $this->activityFactory->createOwnUserPetitionCommentedActivity($comment);
            $this->em->persist($socialActivity);
        }
    }

    public function noticePostCommented(Post\Comment $comment): void
    {
        $socialActivity = $this->activityFactory->createFollowPostCommentedActivity($comment);
        $this->em->persist($socialActivity);
    }

    public function noticePostCommentReplied(Post\Comment $comment): void
    {
        $parentComment = $comment->getParentComment();
        if ($parentComment && $parentComment->getUser()
            && $comment->getUser() !== $parentComment->getUser()) {
            $socialActivity2 = $this->activityFactory->createPostCommentRepliedActivity($comment);
            $this->em->persist($socialActivity2);
        }
    }

    public function noticeOwnPostCommented(Post\Comment $comment): void
    {
        $post = $comment->getPost();
        $user = $post->getUser();
        if ($user && $user->getIsNotifOwnPostChanged()
            && $post->getSubscribers()->contains($user)
            && !$comment->getUser()->isEqualTo($user)
        ) {
            $socialActivity = $this->activityFactory->createOwnPostCommentedActivity($comment);
            $this->em->persist($socialActivity);
        }
    }

    public function noticeGroupsPermissionsChanged(Group $group): void
    {
        /** @var User $user */
        foreach ($group->getUsers() as $user) {
            $socialActivity = $this->activityFactory->createGroupPermissionsChangedActivity($group, $user);
            $this->em->persist($socialActivity);
        }
        $this->em->flush();
    }

    public function noticeCommentMentioned(BaseComment $comment, User ...$users): void
    {
        $group = null;
        if ($comment instanceof UserPetition\Comment) {
            $group = $comment->getPetition()->getGroup();
        } elseif ($comment instanceof Post\Comment) {
            $group = $comment->getPost()->getGroup();
        } elseif ($comment instanceof Poll\Comment) {
            $group = $comment->getQuestion()->getOwner();
        }
        $user = $comment->getUser();
        $recipients = $this->repository->filterByGroupAndFollower($group, $user, ...$users);

        foreach ($recipients as $recipient) {
            $socialActivity = $this->activityFactory->createCommentMentionedActivity($comment, $group, $recipient);
            $this->em->persist($socialActivity);
        }
        $this->em->flush();
    }

    public function noticePostMentioned(Post $post, User ...$users): void
    {
        $group = $post->getGroup();
        $user = $post->getUser();
        $recipients = $this->repository->filterByGroupAndFollower($group, $user, ...$users);

        foreach ($recipients as $recipient) {
            $socialActivity = $this->activityFactory->createPostMentionedActivity($post, $group, $recipient);
            $this->em->persist($socialActivity);
        }
        $this->em->flush();
    }
}

<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityManager;

class SocialActivityManager
{
    const PREVIEW_LENGTH = 20;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function sendUserFollowRequest(UserFollow $follow)
    {
        $socialActivity = SocialActivityFactory::createFollowRequestActivity($follow);

        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);
    }

    public function noticeGroupJoiningApproved(User $user, Group $group)
    {
        $socialActivity = SocialActivityFactory::createJoinToGroupApproved($user, $group);

        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);
    }

    public function noticeUserPetitionCreated(UserPetition $petition)
    {
        $socialActivity = SocialActivityFactory::createFollowUserPetitionCreated($petition);

        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);
    }

    public function noticePostCreated(Post $post)
    {
        $socialActivity = SocialActivityFactory::createFollowPostCreatedActivity($post);

        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);
    }

    public function noticeAnsweredToQuestion(Answer $answer)
    {
        $question = $answer->getQuestion();
        if (!$question->getOwner() instanceof Group) {
            return;
        }
        if ($question->getSubscribers()->contains($question->getUser())) {
            $socialActivity = SocialActivityFactory::createOwnPollAnsweredActivity($answer);

            $this->em->persist($socialActivity);
            $this->em->flush($socialActivity);
        }
    }

    public function noticePollCommented(Poll\Comment $comment)
    {
        $question = $comment->getQuestion();
        if (!$question->getOwner() instanceof Group) {
            return;
        }

        $socialActivity1 = SocialActivityFactory::createFollowPollCommentedActivity($comment);

        $this->em->persist($socialActivity1);

        if ($comment->getParentComment() && $comment->getParentComment()->getUser()
            && $comment->getUser() !== $comment->getParentComment()->getUser()) {
            $socialActivity2 = SocialActivityFactory::createPollCommentRepliedActivity($comment);

            $this->em->persist($socialActivity2);
        }

        if ($question->getUser()->getIsNotifOwnPostChanged()
            && $question->getSubscribers()->contains($question->getUser())
            && !$comment->getUser()->isEqualTo($question->getUser())
            && !in_array($question->getUser(), $comment->getMentionedUsers())
        ) {
            $socialActivity3 = SocialActivityFactory::createOwnPollCommentedActivity($comment);

            $this->em->persist($socialActivity3);
        }
        $this->em->flush();
    }

    public function noticeUserPetitionCommented(UserPetition\Comment $comment)
    {
        $petition = $comment->getPetition();

        $socialActivity1 = SocialActivityFactory::createFollowUserPetitionCommentedActivity($comment);

        $this->em->persist($socialActivity1);

        if ($comment->getParentComment() && $comment->getParentComment()->getUser()
            && $comment->getUser() !== $comment->getParentComment()->getUser()) {
            $socialActivity2 = SocialActivityFactory::createUserPetitionCommentRepliedActivity($comment);

            $this->em->persist($socialActivity2);
        }

        if ($petition->getUser()->getIsNotifOwnPostChanged()
            && $petition->getSubscribers()->contains($petition->getUser())
            && !$comment->getUser()->isEqualTo($petition->getUser())
            && !in_array($petition->getUser(), $comment->getMentionedUsers())
        ) {
            $socialActivity3 = SocialActivityFactory::createOwnUserPetitionCommentedActivity($comment);

            $this->em->persist($socialActivity3);
        }
        $this->em->flush();
    }

    public function noticePostCommented(Post\Comment $comment)
    {
        $post = $comment->getPost();

        $socialActivity = SocialActivityFactory::createFollowPostCommentedActivity($comment);

        $this->em->persist($socialActivity);

        if ($comment->getParentComment() && $comment->getParentComment()->getUser()
            && $comment->getUser() !== $comment->getParentComment()->getUser()) {
            $socialActivity2 = SocialActivityFactory::createPostCommentRepliedActivity($comment);

            $this->em->persist($socialActivity2);
        }

        if ($post->getUser()->getIsNotifOwnPostChanged()
            && $post->getSubscribers()->contains($post->getUser())
            && !$comment->getUser()->isEqualTo($post->getUser())
            && !in_array($post->getUser(), $comment->getMentionedUsers())
        ) {
            $socialActivity3 = SocialActivityFactory::createOwnPostCommentedActivity($comment);

            $this->em->persist($socialActivity3);
        }
        $this->em->flush();
    }

    public function noticeGroupsPermissionsChanged(Group $group)
    {
        /** @var User $user */
        foreach ($group->getUsers() as $user) {
            $socialActivity = SocialActivityFactory::createGroupPermissionsChangedActivity($group, $user);
            $this->em->persist($socialActivity);
            $this->em->flush($socialActivity);
        }
    }

    public function noticeCommentMentioned(BaseComment $comment)
    {
        $recipients = $comment->getMentionedUsers();
        if (empty($recipients)) {
            return;
        }
        $group = null;
        if ($comment instanceof UserPetition\Comment) {
            $group = $comment->getPetition()->getGroup();
        } elseif ($comment instanceof Post\Comment) {
            $group = $comment->getPost()->getGroup();
        } elseif ($comment instanceof Poll\Comment) {
            $group = $comment->getQuestion()->getOwner();
        }

        $user = $comment->getUser();

        foreach ($recipients as $recipient) {
            if ($comment->getUser()->getId() !== $recipient->getId()
                && (($group instanceof Group &&
                $this->em->getRepository(UserGroup::class)
                    ->isJoinedUser($group, $recipient))
            || $this->em->getRepository(UserFollow::class)
                    ->findActiveFollower($user, $recipient))
            ) {
                $socialActivity = SocialActivityFactory::createCommentMentionedActivity($comment, $group, $recipient);
                $this->em->persist($socialActivity);
                $this->em->flush($socialActivity);
            }
        }
    }

    public function noticePostMentioned(Post $post)
    {
        $recipients = $post->getMentionedUsers();
        if (empty($recipients)) {
            return;
        }

        $group = $post->getGroup();
        $user = $post->getUser();

        foreach ($recipients as $recipient) {
            if (($group instanceof Group &&
                    $this->em->getRepository(UserGroup::class)
                        ->isJoinedUser($group, $recipient))
                || $this->em->getRepository(UserFollow::class)->findActiveFollower($user, $recipient)
            ) {
                $socialActivity = SocialActivityFactory::createPostMentionedActivity($post, $group, $recipient);
                $this->em->persist($socialActivity);
                $this->em->flush($socialActivity);
            }
        }
    }
}

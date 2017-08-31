<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Entity\UserPetition;

class SocialActivityFactory
{
    const PREVIEW_LENGTH = 20;

    public static function createFollowRequestActivity(UserFollow $userFollow): SocialActivity
    {
        return SocialActivity::createFollowRequest()
            ->setTarget(self::getFollowRequestTarget($userFollow))
            ->setRecipient($userFollow->getUser());
    }

    public static function getFollowRequestTarget(UserFollow $userFollow): array
    {
        return [
            'id' => $userFollow->getFollower()->getId(),
            'type' => 'user',
            'full_name' => $userFollow->getFollower()->getFullName(),
            'image' => $userFollow->getFollower()->getAvatarFileName(),
        ];
    }

    public static function createJoinToGroupApproved(User $user, Group $group)
    {
        return SocialActivity::createJoinToGroupApproved($group)
            ->setTarget(self::getJoinToGroupApprovedTarget($user, $group))
            ->setRecipient($user);
    }

    public static function getJoinToGroupApprovedTarget(User $user, Group $group): array
    {
        return [
            'type' => 'group',
            'id' => $group->getId(),
            'user' => $user->getId(),
        ];
    }

    public static function createFollowUserPetitionCreated(UserPetition $petition)
    {
        return SocialActivity::createFollowUserPetitionCreated($petition->getUser(), $petition->getGroup())
            ->setTarget(self::getFollowedUserPetitionTarget($petition));
    }

    public static function getFollowedUserPetitionTarget(UserPetition $petition): array
    {
        return [
            'id' => $petition->getId(),
            'title' => $petition->getTitle(),
            'body' => $petition->getBody(),
            'type' => 'user-petition',
            'full_name' => $petition->getUser()->getFullName(),
            'image' => $petition->getUser()->getAvatarFileName(),
        ];
    }

    public static function createFollowPostCreatedActivity(Post $post)
    {
        return SocialActivity::createFollowPostCreated($post->getUser(), $post->getGroup())
            ->setTarget(self::getFollowPostCreatedTarget($post));
    }

    public static function getFollowPostCreatedTarget(Post $post): array
    {
        return [
            'id' => $post->getId(),
            'body' => $post->getBody(),
            'type' => 'post',
            'full_name' => $post->getUser()->getFullName(),
            'image' => $post->getUser()->getAvatarFileName(),
        ];
    }

    public static function createOwnPollAnsweredActivity(Poll\Answer $answer)
    {
        $question = $answer->getQuestion();

        return SocialActivity::createOwnPollAnswered($question->getOwner())
            ->setTarget(self::getOwnPollAnsweredTarget($answer))
            ->setRecipient($question->getUser());
    }

    public static function getOwnPollAnsweredTarget(Poll\Answer $answer): array
    {
        $question = $answer->getQuestion();

        return [
            'id' => $question->getId(),
            'type' => $question->getType(),
            'label' => self::getLabelByPoll($question),
            'preview' => self::getPreviewByPoll($question),
            'full_name' => $answer->getUser()->getFullName(),
            'image' => $answer->getUser()->getAvatarFileName(),
        ];
    }

    public static function createFollowPollCommentedActivity(Poll\Comment $comment)
    {
        return SocialActivity::createFollowPollCommented($comment->getUser(), $comment->getQuestion()->getOwner())
            ->setTarget(self::getPollCommentedTarget($comment));
    }

    public static function getPollCommentedTarget(Poll\Comment $comment): array
    {
        $question = $comment->getQuestion();
        $target = [
            'id' => $question->getId(),
            'type' => $question->getType(),
            'full_name' => $comment->getUser()->getFullName(),
            'image' => $comment->getUser()->getAvatarFileName(),
            'label' => self::getLabelByPoll($question),
            'preview' => $comment->getCommentBody(),
        ];
        if ($comment->getParentComment()) {
            $target['comment_id'] = $comment->getId();
        }

        return $target;
    }

    public static function createPollCommentRepliedActivity(Poll\Comment $comment)
    {
        $parentComment = $comment->getParentComment();

        return SocialActivity::createCommentReplied($comment->getQuestion()->getOwner())
            ->setTarget(self::getPollCommentedTarget($comment))
            ->setRecipient($parentComment->getUser());
    }

    public static function createOwnPollCommentedActivity(Poll\Comment $comment)
    {
        $question = $comment->getQuestion();

        return SocialActivity::createOwnPollCommented($question->getGroup())
            ->setTarget(self::getPollCommentedTarget($comment))
            ->setRecipient($question->getUser());
    }

    public static function createFollowUserPetitionCommentedActivity(UserPetition\Comment $comment)
    {
        $petition = $comment->getPetition();

        return SocialActivity::createFollowUserPetitionCommented($comment->getUser(), $petition->getGroup())
            ->setTarget(self::getUserPetitionCommentedTarget($comment));
    }

    public static function getUserPetitionCommentedTarget(UserPetition\Comment $comment): array
    {
        $petition = $comment->getPetition();
        $target = [
            'id' => $petition->getId(),
            'preview' => $comment->getCommentBody(),
            'type' => 'user-petition',
            'label' => 'petition',
            'full_name' => $comment->getUser()->getFullName(),
            'image' => $comment->getUser()->getAvatarFileName(),
        ];
        if ($comment->getParentComment()) {
            $target['comment_id'] = $comment->getId();
        }

        return $target;
    }

    public static function createUserPetitionCommentRepliedActivity(UserPetition\Comment $comment)
    {
        $petition = $comment->getPetition();
        $parentComment = $comment->getParentComment();

        return SocialActivity::createCommentReplied($petition->getGroup())
            ->setTarget(self::getUserPetitionCommentedTarget($comment))
            ->setRecipient($parentComment->getUser());
    }

    public static function createOwnUserPetitionCommentedActivity(UserPetition\Comment $comment)
    {
        $petition = $comment->getPetition();

        return SocialActivity::createOwnUserPetitionCommented($petition->getGroup())
            ->setTarget(self::getUserPetitionCommentedTarget($comment))
            ->setRecipient($petition->getUser());
    }

    public static function createFollowPostCommentedActivity(Post\Comment $comment)
    {
        return SocialActivity::createFollowPostCommented($comment->getUser(), $comment->getPost()->getGroup())
            ->setTarget(self::getPostCommentedTarget($comment));
    }

    public static function getPostCommentedTarget(Post\Comment $comment): array
    {
        $post = $comment->getPost();
        $target = [
            'id' => $post->getId(),
            'preview' => $comment->getCommentBody(),
            'type' => 'post',
            'label' => 'post',
            'full_name' => $comment->getUser()->getFullName(),
            'image' => $comment->getUser()->getAvatarFileName(),
        ];
        if ($comment->getParentComment()) {
            $target['comment_id'] = $comment->getId();
        }

        return $target;
    }

    public static function createPostCommentRepliedActivity(Post\Comment $comment)
    {
        $parentComment = $comment->getParentComment();

        return SocialActivity::createCommentReplied($comment->getPost()->getGroup())
            ->setTarget(self::getPostCommentedTarget($comment))
            ->setRecipient($parentComment->getUser());
    }

    public static function createOwnPostCommentedActivity(Post\Comment $comment)
    {
        $post = $comment->getPost();

        return SocialActivity::createOwnPostCommented($post->getGroup())
            ->setTarget(self::getPostCommentedTarget($comment))
            ->setRecipient($post->getUser());
    }

    public static function createGroupPermissionsChangedActivity(Group $group, User $user)
    {
        return SocialActivity::createGroupPermissionsChanged($group)
            ->setTarget(self::getGroupPermissionsChangedTarget($group))
            ->setRecipient($user);
    }

    public static function getGroupPermissionsChangedTarget(Group $group): array
    {
        return [
            'id' => $group->getId(),
            'type' => 'group',
        ];
    }

    public static function createCommentMentionedActivity(BaseComment $comment, Group $group, User $user)
    {
        return SocialActivity::createCommentMentioned($group)
            ->setTarget(self::getCommentMentionedTarget($comment))
            ->setRecipient($user);
    }

    public static function getCommentMentionedTarget(BaseComment $comment): array
    {
        if ($comment instanceof UserPetition\Comment) {
            $petition = $comment->getPetition();
            $target = [
                'id' => $petition->getId(),
                'preview' => self::preparePreview($comment->getCommentBody()),
                'type' => 'user-petition',
                'label' => 'petition',
            ];
        } elseif ($comment instanceof Post\Comment) {
            $post = $comment->getPost();
            $target = [
                'id' => $post->getId(),
                'preview' => self::preparePreview($comment->getCommentBody()),
                'type' => 'post',
                'label' => 'post',
            ];
        } elseif ($comment instanceof Poll\Comment) {
            $question = $comment->getQuestion();
            $target = [
                'id' => $question->getId(),
                'preview' => self::preparePreview($comment->getCommentBody()),
                'type' => $question->getType(),
                'label' => self::getLabelByPoll($question),
            ];
        }
        $parentComment = $comment->getParentComment();
        if ($parentComment && $parentComment->getUser()) {
            $target['comment_id'] = $comment->getId();
        }

        $user = $comment->getUser();
        $target['user_id'] = $user->getId();
        $target['full_name'] = $user->getFullName();
        $target['image'] = $user->getAvatarFileName();

        return $target;
    }

    public static function createPostMentionedActivity(Post $post, Group $group, User $user)
    {
        return SocialActivity::createPostMentioned($group)
            ->setTarget(self::getPostMentionedTarget($post))
            ->setRecipient($user);
    }

    public static function getPostMentionedTarget(Post $post): array
    {
        $user = $post->getUser();

        return [
            'id' => $post->getId(),
            'preview' => self::preparePreview($post->getBody()),
            'type' => 'post',
            'label' => 'post',
            'user_id' => $user->getId(),
            'full_name' => $user->getFullName(),
            'image' => $user->getAvatarFileName(),
        ];
    }

    private static function preparePreview(string $text = ''): string
    {
        if (mb_strlen($text) > self::PREVIEW_LENGTH) {
            return mb_substr($text, 0, 20, 'utf8').'...';
        }

        return $text;
    }

    private static function getPreviewByPoll(Question $question): string
    {
        if ($question instanceof Question\Petition) {
            return self::preparePreview($question->getPetitionTitle());
        }
        if ($question instanceof Question\PaymentRequest) {
            return self::preparePreview($question->getTitle());
        }
        if ($question instanceof Question\LeaderEvent) {
            return self::preparePreview($question->getTitle());
        }

        return self::preparePreview($question->getSubject());
    }

    private static function getLabelByPoll(Question $question): string
    {
        if ($question instanceof Question\Petition) {
            return 'petition';
        }
        if ($question instanceof Question\PaymentRequest) {
            return 'payment request';
        }
        if ($question instanceof Question\LeaderEvent) {
            return 'event';
        }

        return 'question';
    }
}
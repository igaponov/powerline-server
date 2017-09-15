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

    public function createFollowRequestActivity(UserFollow $userFollow): SocialActivity
    {
        return SocialActivity::createFollowRequest()
            ->setTarget($this->getFollowRequestTarget($userFollow))
            ->setRecipient($userFollow->getUser());
    }

    public function getFollowRequestTarget(UserFollow $userFollow): array
    {
        return [
            'id' => $userFollow->getFollower()->getId(),
            'type' => 'user',
            'full_name' => $userFollow->getFollower()->getFullName(),
            'image' => $userFollow->getFollower()->getAvatarFileName(),
        ];
    }

    public function createJoinToGroupApprovedActivity(User $user, Group $group)
    {
        return SocialActivity::createJoinToGroupApproved($group)
            ->setTarget($this->getJoinToGroupApprovedTarget($user, $group))
            ->setRecipient($user);
    }

    public function getJoinToGroupApprovedTarget(User $user, Group $group): array
    {
        return [
            'type' => 'group',
            'id' => $group->getId(),
            'user' => $user->getId(),
        ];
    }

    public function createFollowUserPetitionCreatedActivity(UserPetition $petition)
    {
        return SocialActivity::createFollowUserPetitionCreated($petition->getUser(), $petition->getGroup())
            ->setTarget($this->getFollowedUserPetitionTarget($petition));
    }

    public function getFollowedUserPetitionTarget(UserPetition $petition): array
    {
        $user = $petition->getUser();

        return [
            'id' => $petition->getId(),
            'title' => $petition->getTitle(),
            'body' => $petition->getBody(),
            'type' => 'user-petition',
            'full_name' => $user ? $user->getFullName() : '',
            'image' => $user ? $user->getAvatarFileName() : '',
        ];
    }

    public function createFollowPostCreatedActivity(Post $post)
    {
        return SocialActivity::createFollowPostCreated($post->getUser(), $post->getGroup())
            ->setTarget($this->getFollowPostCreatedTarget($post));
    }

    public function getFollowPostCreatedTarget(Post $post): array
    {
        $user = $post->getUser();

        return [
            'id' => $post->getId(),
            'body' => $post->getBody(),
            'type' => 'post',
            'full_name' => $user ? $user->getFullName() : '',
            'image' => $user ? $user->getAvatarFileName() : '',
        ];
    }

    public function createOwnPollAnsweredActivity(Poll\Answer $answer)
    {
        $question = $answer->getQuestion();

        return SocialActivity::createOwnPollAnswered($question->getOwner())
            ->setTarget($this->getOwnPollAnsweredTarget($answer))
            ->setRecipient($question->getUser());
    }

    public function getOwnPollAnsweredTarget(Poll\Answer $answer): array
    {
        $question = $answer->getQuestion();

        return [
            'id' => $question->getId(),
            'type' => $question->getType(),
            'label' => $this->getLabelByPoll($question),
            'preview' => $this->getPreviewByPoll($question),
            'full_name' => $answer->getUser()->getFullName(),
            'image' => $answer->getUser()->getAvatarFileName(),
        ];
    }

    public function createFollowPollCommentedActivity(Poll\Comment $comment)
    {
        return SocialActivity::createFollowPollCommented($comment->getUser(), $comment->getQuestion()->getOwner())
            ->setTarget($this->getPollCommentedTarget($comment));
    }

    public function getPollCommentedTarget(Poll\Comment $comment): array
    {
        $question = $comment->getQuestion();
        $target = [
            'id' => $question->getId(),
            'type' => $question->getType(),
            'full_name' => $comment->getUser()->getFullName(),
            'image' => $comment->getUser()->getAvatarFileName(),
            'label' => $this->getLabelByPoll($question),
            'preview' => $comment->getCommentBody(),
        ];
        if ($comment->getParentComment()) {
            $target['comment_id'] = $comment->getId();
        }

        return $target;
    }

    public function createPollCommentRepliedActivity(Poll\Comment $comment)
    {
        $parentComment = $comment->getParentComment();

        return SocialActivity::createCommentReplied($comment->getQuestion()->getOwner())
            ->setTarget($this->getPollCommentedTarget($comment))
            ->setRecipient($parentComment->getUser());
    }

    public function createOwnPollCommentedActivity(Poll\Comment $comment)
    {
        $question = $comment->getQuestion();

        return SocialActivity::createOwnPollCommented($question->getGroup())
            ->setTarget($this->getPollCommentedTarget($comment))
            ->setRecipient($question->getUser());
    }

    public function createFollowUserPetitionCommentedActivity(UserPetition\Comment $comment)
    {
        $petition = $comment->getPetition();

        return SocialActivity::createFollowUserPetitionCommented($comment->getUser(), $petition->getGroup())
            ->setTarget($this->getUserPetitionCommentedTarget($comment));
    }

    public function getUserPetitionCommentedTarget(UserPetition\Comment $comment): array
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

    public function createUserPetitionCommentRepliedActivity(UserPetition\Comment $comment)
    {
        $petition = $comment->getPetition();
        $parentComment = $comment->getParentComment();

        return SocialActivity::createCommentReplied($petition->getGroup())
            ->setTarget($this->getUserPetitionCommentedTarget($comment))
            ->setRecipient($parentComment->getUser());
    }

    public function createOwnUserPetitionCommentedActivity(UserPetition\Comment $comment)
    {
        $petition = $comment->getPetition();

        return SocialActivity::createOwnUserPetitionCommented($petition->getGroup())
            ->setTarget($this->getUserPetitionCommentedTarget($comment))
            ->setRecipient($petition->getUser());
    }

    public function createFollowPostCommentedActivity(Post\Comment $comment)
    {
        return SocialActivity::createFollowPostCommented($comment->getUser(), $comment->getPost()->getGroup())
            ->setTarget($this->getPostCommentedTarget($comment));
    }

    public function getPostCommentedTarget(Post\Comment $comment): array
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

    public function createPostCommentRepliedActivity(Post\Comment $comment)
    {
        $parentComment = $comment->getParentComment();

        return SocialActivity::createCommentReplied($comment->getPost()->getGroup())
            ->setTarget($this->getPostCommentedTarget($comment))
            ->setRecipient($parentComment->getUser());
    }

    public function createOwnPostCommentedActivity(Post\Comment $comment)
    {
        $post = $comment->getPost();

        return SocialActivity::createOwnPostCommented($post->getGroup())
            ->setTarget($this->getPostCommentedTarget($comment))
            ->setRecipient($post->getUser());
    }

    public function createGroupPermissionsChangedActivity(Group $group, User $user)
    {
        return SocialActivity::createGroupPermissionsChanged($group)
            ->setTarget($this->getGroupPermissionsChangedTarget($group))
            ->setRecipient($user);
    }

    public function getGroupPermissionsChangedTarget(Group $group): array
    {
        return [
            'id' => $group->getId(),
            'type' => 'group',
        ];
    }

    public function createCommentMentionedActivity(BaseComment $comment, Group $group, User $user)
    {
        return SocialActivity::createCommentMentioned($group)
            ->setTarget($this->getCommentMentionedTarget($comment))
            ->setRecipient($user);
    }

    public function getCommentMentionedTarget(BaseComment $comment): array
    {
        if ($comment instanceof UserPetition\Comment) {
            $petition = $comment->getPetition();
            $target = [
                'id' => $petition->getId(),
                'preview' => $this->preparePreview($comment->getCommentBody()),
                'type' => 'user-petition',
                'label' => 'petition',
            ];
        } elseif ($comment instanceof Post\Comment) {
            $post = $comment->getPost();
            $target = [
                'id' => $post->getId(),
                'preview' => $this->preparePreview($comment->getCommentBody()),
                'type' => 'post',
                'label' => 'post',
            ];
        } elseif ($comment instanceof Poll\Comment) {
            $question = $comment->getQuestion();
            $target = [
                'id' => $question->getId(),
                'preview' => $this->preparePreview($comment->getCommentBody()),
                'type' => $question->getType(),
                'label' => $this->getLabelByPoll($question),
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

    public function createPostMentionedActivity(Post $post, Group $group, User $user)
    {
        return SocialActivity::createPostMentioned($group)
            ->setTarget($this->getPostMentionedTarget($post))
            ->setRecipient($user);
    }

    public function getPostMentionedTarget(Post $post): array
    {
        $user = $post->getUser();

        return [
            'id' => $post->getId(),
            'preview' => $this->preparePreview($post->getBody()),
            'type' => 'post',
            'label' => 'post',
            'user_id' => $user->getId(),
            'full_name' => $user->getFullName(),
            'image' => $user->getAvatarFileName(),
        ];
    }

    private function preparePreview(string $text = ''): string
    {
        if (mb_strlen($text) > self::PREVIEW_LENGTH) {
            return mb_substr($text, 0, 20, 'utf8').'...';
        }

        return $text;
    }

    private function getPreviewByPoll(Question $question): string
    {
        if ($question instanceof Question\Petition) {
            return $this->preparePreview($question->getPetitionTitle());
        }
        if ($question instanceof Question\PaymentRequest) {
            return $this->preparePreview($question->getTitle());
        }
        if ($question instanceof Question\LeaderEvent) {
            return $this->preparePreview($question->getTitle());
        }

        return $this->preparePreview($question->getSubject());
    }

    private function getLabelByPoll(Question $question): string
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
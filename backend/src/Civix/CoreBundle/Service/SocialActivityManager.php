<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SocialActivity;
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
        $socialActivity = (new SocialActivity(SocialActivity::TYPE_FOLLOW_REQUEST))
            ->setTarget([
                'id' => $follow->getFollower()->getId(),
                'type' => 'user',
                'full_name' => $follow->getFollower()->getFullName(),
                'image' => $follow->getFollower()->getAvatarFileName(),
            ])
            ->setRecipient($follow->getUser())
        ;
        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);

        return $socialActivity;
    }

    public function noticeGroupJoiningApproved(User $user, Group $group)
    {
        $socialActivity = (new SocialActivity(SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED, null, $group))
            ->setTarget(['id' => $group->getId(), 'type' => 'group'])
            ->setRecipient($user)
        ;
        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);

        return $socialActivity;
    }

    public function noticeUserPetitionCreated(UserPetition $petition)
    {
        $socialActivity = (new SocialActivity(SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED, $petition->getUser(),
            $petition->getGroup()))
            ->setTarget([
                'id' => $petition->getId(),
                'title' => $petition->getTitle(),
                'body' => $petition->getBody(),
                'type' => 'user-petition',
                'full_name' => $petition->getUser()->getFullName(),
                'image' => $petition->getUser()->getAvatarFileName(),
            ])
        ;
        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);

        return $socialActivity;
    }

    public function noticePostCreated(Post $post)
    {
        $socialActivity = (new SocialActivity(SocialActivity::TYPE_FOLLOW_POST_CREATED, $post->getUser(),
            $post->getGroup()))
            ->setTarget([
                'id' => $post->getId(),
                'body' => $post->getBody(),
                'type' => 'post',
                'full_name' => $post->getUser()->getFullName(),
                'image' => $post->getUser()->getAvatarFileName(),
            ])
        ;
        $this->em->persist($socialActivity);
        $this->em->flush($socialActivity);

        return $socialActivity;
    }

    public function noticeAnsweredToQuestion(Answer $answer)
    {
        $question = $answer->getQuestion();
        if (!$question->getOwner() instanceof Group) {
            return null;
        }
        if ($question->getSubscribers()->contains($question->getUser())) {
            $target = [
                'id' => $question->getId(),
                'type' => $question->getType(),
                'label' => $this->getLabelByPoll($question),
                'preview' => $this->getPreviewByPoll($question),
                'full_name' => $answer->getUser()->getFullName(),
                'image' => $answer->getUser()->getAvatarFileName(),
            ];
            $socialActivity = (new SocialActivity(
                SocialActivity::TYPE_OWN_POLL_ANSWERED,
                null,
                $answer->getQuestion()
                    ->getOwner()
            ))->setTarget($target)
                ->setRecipient($question->getUser());
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
        $target = [
            'id' => $question->getId(),
            'type' => $question->getType(),
            'full_name' => $comment->getUser()->getFullName(),
            'image' => $comment->getUser()->getAvatarFileName(),
            'label' => $this->getLabelByPoll($question),
            'preview' => $comment->getCommentBody(),
        ];
        if ($question->getSubscribers()->contains($question->getUser())) {
            $socialActivity1 = (new SocialActivity(
                SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
                null,
                $comment->getQuestion()
                    ->getOwner()
            ))->setTarget($target);
            if ($comment->getParentComment()) {
                $target['comment_id'] = $comment->getId();
            }
            $this->em->persist($socialActivity1);
        }

        if ($comment->getParentComment() && $comment->getParentComment()->getUser()
            && $comment->getUser() !== $comment->getParentComment()->getUser()) {
            $socialActivity2 = (new SocialActivity(SocialActivity::TYPE_COMMENT_REPLIED, null,
                $comment->getQuestion()->getOwner()))
                ->setTarget($target)
                ->setRecipient($comment->getParentComment()->getUser())
            ;
            $this->em->persist($socialActivity2);
        }

        if ($question->getUser()->getIsNotifOwnPostChanged() && $question->getSubscribers()->contains($question->getUser())) {
            $socialActivity3 = new SocialActivity(
                SocialActivity::TYPE_OWN_POLL_COMMENTED,
                null,
                $question->getGroup()
            );
            $socialActivity3->setTarget($target)
                ->setRecipient($question->getUser())
            ;
            $this->em->persist($socialActivity3);
        }
        $this->em->flush();
    }

    public function noticeUserPetitionCommented(UserPetition\Comment $comment)
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
        $socialActivity = (new SocialActivity(SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED, null,
            $petition->getGroup()))
            ->setTarget($target)
        ;
        $this->em->persist($socialActivity);

        if ($comment->getParentComment() && $comment->getParentComment()->getUser()
            && $comment->getUser() !== $comment->getParentComment()->getUser()) {
            $socialActivity2 = (new SocialActivity(SocialActivity::TYPE_COMMENT_REPLIED, null,
                $petition->getGroup()))
                ->setTarget($target)
                ->setRecipient($comment->getParentComment()->getUser())
            ;
            $this->em->persist($socialActivity2);
        }

        if ($petition->getUser()->getIsNotifOwnPostChanged() && $petition->getSubscribers()->contains($petition->getUser())) {
            $socialActivity3 = new SocialActivity(
                SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED,
                null,
                $petition->getGroup()
            );
                $socialActivity3->setTarget($target)
                ->setRecipient($petition->getUser())
            ;
            $this->em->persist($socialActivity3);
        }
        $this->em->flush();
    }

    public function noticePostCommented(Post\Comment $comment)
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
        $socialActivity = (new SocialActivity(SocialActivity::TYPE_FOLLOW_POST_COMMENTED, null,
            $post->getGroup()))
            ->setTarget($target)
        ;
        $this->em->persist($socialActivity);

        if ($comment->getParentComment() && $comment->getParentComment()->getUser()
            && $comment->getUser() !== $comment->getParentComment()->getUser()) {
            $socialActivity2 = (new SocialActivity(SocialActivity::TYPE_COMMENT_REPLIED, null,
                $post->getGroup()))
                ->setTarget($target)
                ->setRecipient($comment->getParentComment()->getUser())
            ;
            $this->em->persist($socialActivity2);
        }

        if ($post->getUser()->getIsNotifOwnPostChanged() && $post->getSubscribers()->contains($post->getUser()) && $comment->getUser() != $post->getUser()) {
            $socialActivity3 = new SocialActivity(
                SocialActivity::TYPE_OWN_POST_COMMENTED,
                null,
                $post->getGroup()
            );
                $socialActivity3->setTarget($target)
                ->setRecipient($post->getUser())
            ;
            $this->em->persist($socialActivity3);
        }
        $this->em->flush();
    }

    public function noticeGroupsPermissionsChanged(Group $group)
    {
        $target = [
            'id' => $group->getId(),
            'type' => 'group',
        ];
        /** @var User $user */
        foreach ($group->getUsers() as $user) {
            $socialActivity = (new SocialActivity(SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED, null, $group))
                ->setTarget($target)
                ->setRecipient($user)
            ;
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
            $petition = $comment->getPetition();
            $group = $petition->getGroup();
            $target = [
                'id' => $petition->getId(),
                'preview' => $this->preparePreview($comment->getCommentBody()),
                'type' => 'user-petition',
                'label' => 'petition',
            ];
        } elseif ($comment instanceof Post\Comment) {
            $post = $comment->getPost();
            $group = $post->getGroup();
            $target = [
                'id' => $post->getId(),
                'preview' => $this->preparePreview($comment->getCommentBody()),
                'type' => 'post',
                'label' => 'post',
            ];
        } elseif ($comment instanceof Poll\Comment) {
            $question = $comment->getQuestion();
            $group = $question->getOwner();
            $target = [
                'id' => $question->getId(),
                'preview' => $this->preparePreview($comment->getCommentBody()),
                'type' => $question->getType(),
                'label' => $this->getLabelByPoll($question),
            ];
        }
        if ($comment->getParentComment()->getUser()) {
            $target['comment_id'] = $comment->getId();
        }

        $user = $comment->getUser();
        $target['user_id'] = $user->getId();
        $target['full_name'] = $user->getFullName();
        $target['image'] = $user->getAvatarFileName();

        foreach ($recipients as $recipient) {
            if ($group instanceof Group &&
                $this->em->getRepository(UserGroup::class)
                    ->isJoinedUser($group, $recipient)) {
                $socialActivity = (new SocialActivity(SocialActivity::TYPE_COMMENT_MENTIONED, null, $group))
                    ->setTarget($target)
                    ->setRecipient($recipient)
                ;
                $this->em->persist($socialActivity);
                $this->em->flush($socialActivity);
            } elseif ($this->em->getRepository(UserFollow::class)->findActiveFollower($user, $recipient)) {
                $socialActivity = (new SocialActivity(SocialActivity::TYPE_COMMENT_MENTIONED, $user, null))
                    ->setTarget($target)
                    ->setRecipient($recipient)
                ;
                $this->em->persist($socialActivity);
                $this->em->flush($socialActivity);
            }
        }
    }

    private function preparePreview($text = '')
    {
        if (mb_strlen($text) > self::PREVIEW_LENGTH) {
            return mb_substr($text, 0, 20, 'utf8').'...';
        }

        return $text;
    }

    private function getPreviewByPoll(Question $question)
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

    private function getLabelByPoll(Question $question)
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

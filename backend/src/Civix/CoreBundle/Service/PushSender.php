<?php

namespace Civix\CoreBundle\Service;

use Civix\Component\Notification\PushMessage;
use Civix\Component\Notification\Sender;
use Civix\CoreBundle\Entity\Announcement\GroupAnnouncement;
use Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\Poll\QuestionUserPush;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PushSender
{
    const INVITE_PUSH_MESSAGE = 'You have been invited to join this group';

    const TYPE_PUSH_ACTIVITY = 'activity';
    const TYPE_PUSH_ANNOUNCEMENT = 'announcement';
    const TYPE_PUSH_INVITE = 'invite';
    const TYPE_PUSH_OWN_USER_PETITION_BOOSTED = 'own-user-petition-is-boosted';
    const TYPE_PUSH_USER_PETITION_BOOSTED = 'user-petition-is-boosted';
    const TYPE_PUSH_OWN_POST_BOOSTED = 'own-post-is-boosted';
    const TYPE_PUSH_POST_BOOSTED = 'post-is-boosted';
    const TYPE_PUSH_POST_SHARED = 'post-is-shared';
    const TYPE_PUSH_PETITION_SHARED = 'petition-is-shared';
    /*Not used in push notification but reserved and use in settings*/
    const TYPE_PUSH_PETITION = 'petition';
    const TYPE_PUSH_NEWS = 'leader_news';
    const TYPE_PUSH_EVENT = 'leader_event';
    const TYPE_PUSH_SOCIAL_ACTIVITY = 'social_activity';

    const MAX_USERS_PER_QUERY = 5000;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    protected $questionUsersPush;
    /**
     * @var Sender
     */
    protected $sender;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        QuestionUserPush $questionUsersPush,
        Sender $sender,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->questionUsersPush = $questionUsersPush;
        $this->sender = $sender;
        $this->logger = $logger;
    }

    /**
     * Leader publishes poll, petition, discussion, fundraiser, event (all group members notified)
     *
     * @param $questionId
     * @param $title
     * @param $message
     */
    public function sendPushPublishQuestion($questionId, $title, $message): void
    {
        /** @var Question $question */
        $question = $this->entityManager
            ->getRepository('CivixCoreBundle:Poll\Question')
            ->find($questionId);

        if (!$question) {
            return;
        }

        $avatar = $question->getGroup()->getAvatarFileName();

        $this->questionUsersPush->setQuestion($question);
        $lastId = 0;

        do {
            /** @var User[] $users */
            $users = $this->questionUsersPush->getUsersForPush($lastId, self::MAX_USERS_PER_QUERY);

            if ($users) {
                foreach ($users as $recipient) {
                    $this->send(
                        $recipient,
                        $title,
                        $message,
                        $question->getType(),
                        [
                            'target' => [
                                'id' => $question->getId(),
                                'type' => 'poll-published',
                            ],
                        ],
                        $avatar
                    );
                    $lastId = $recipient->getId();
                }
            }

            $this->entityManager->clear();
        } while ($users);
    }

    /**
     * User petition is manually boosted by group leader.
     * User petition is boosted automatically by system in a group.
     *
     * @param $groupId
     * @param null $petitionId
     */
    public function sendBoostedPetitionPush($groupId, $petitionId = null): void
    {
        $users = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByGroupForPush($groupId, self::TYPE_PUSH_PETITION);

        $petition = $this->entityManager->getRepository(UserPetition::class)->find($petitionId);

        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                sprintf(
                    '%s in %s',
                    $petition->getUser()
                        ->getFullName(),
                    $petition->getGroup()
                        ->getOfficialName()
                ),
                "Boosted Petition: {$petition->getBody()}",
                $petition->getUser()->isEqualTo($recipient) ? self::TYPE_PUSH_OWN_USER_PETITION_BOOSTED : self::TYPE_PUSH_USER_PETITION_BOOSTED,
                [
                    'target' => [
                        'id' => $petitionId,
                        'type' => 'user-petition-boosted',
                    ],
                ],
                $petition->getUser()->getAvatarFileName()
            );
        }
    }

    /**
     * User post is manually boosted by group leader.
     * User post is boosted automatically by system in a group.
     *
     * @param $groupId
     * @param null $postId
     */
    public function sendBoostedPostPush($groupId, $postId = null): void
    {
        $users = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByGroupForPush($groupId, self::TYPE_PUSH_PETITION);

        $post = $this->entityManager->getRepository(Post::class)->find($postId);

        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                sprintf(
                    '%s in %s',
                    $post->getUser()
                        ->getFullName(),
                    $post->getGroup()
                        ->getOfficialName()
                ),
                "Boosted Post: {$this->preview($post->getBody())}",
                $post->getUser()->isEqualTo($recipient) ? self::TYPE_PUSH_OWN_POST_BOOSTED : self::TYPE_PUSH_POST_BOOSTED,
                [
                    'target' => [
                        'id' => $postId,
                        'type' => 'post-boosted',
                    ],
                ],
                $post->getUser()->getAvatarFileName()
            );
        }
    }

    /**
     * @param integer $userId
     * @param integer $postId
     */
    public function sendSharedPostPush($userId, $postId): void
    {
        $sharer = $this->entityManager->find(User::class, $userId);
        $post = $this->entityManager->find(Post::class, $postId);
        if (!$post || !$sharer || !($author = $post->getUser())) {
            return;
        }
        $iterator = $this->entityManager
                ->getRepository(User::class)
                ->getUsersByGroupAndFollowingForPush($post->getGroup(), $sharer);

        foreach ($iterator as $item) {
            $recipient = $item[0];
            $this->send(
                $recipient,
                $sharer->getFullName(),
                sprintf(
                    "shared %s's post with you: %s",
                    $author->getFirstName(),
                    $this->preview($post->getBody())
                ),
                self::TYPE_PUSH_POST_SHARED,
                [
                    'target' => [
                        'id' => $postId,
                        'type' => 'post-shared',
                    ],
                ],
                $sharer->getAvatarFileName()
            );
        }
    }

    /**
     * @param integer $userId
     * @param integer $petitionId
     */
    public function sendSharedPetitionPush($userId, $petitionId): void
    {
        $sharer = $this->entityManager->find(User::class, $userId);
        $post = $this->entityManager->find(UserPetition::class, $petitionId);
        if (!$post || !$sharer || !($author = $post->getUser())) {
            return;
        }
        $iterator = $this->entityManager
                ->getRepository(User::class)
                ->getUsersByGroupAndFollowingForPush($post->getGroup(), $sharer);

        foreach ($iterator as $item) {
            $recipient = $item[0];
            $this->send(
                $recipient,
                $sharer->getFullName(),
                sprintf(
                    "shared %s's petition with you: %s",
                    $author->getFirstName(),
                    $this->preview($post->getBody())
                ),
                self::TYPE_PUSH_PETITION_SHARED,
                [
                    'target' => [
                        'id' => $petitionId,
                        'type' => 'petition-shared',
                    ],
                ],
                $sharer->getAvatarFileName()
            );
        }
    }

    public function sendPublishedRepresentativeAnnouncementPush($representativeId, $announcementId): void
    {
        /** @var UserRepresentative $representative */
        $representative = $this->entityManager
            ->getRepository(UserRepresentative::class)
            ->find($representativeId);

        if (!$representative) {
            return;
        }
        $users = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUsersByDistrictForPush($representative->getDistrict()->getId(), self::TYPE_PUSH_ANNOUNCEMENT);
        $announcement = $this->entityManager
            ->getRepository(RepresentativeAnnouncement::class)
            ->find($announcementId);
        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                $representative->getOfficialTitle(),
                $this->preview($announcement->getContent()),
                self::TYPE_PUSH_ANNOUNCEMENT,
                [
                    'target' => [
                        'id' => $announcementId,
                        'type' => 'representative-announcement-published',
                    ],
                ]
            );
        }
    }

    /**
     * Announcement is published by group leader (all group members notified)
     *
     * @param $groupId
     * @param $announcementId
     */
    public function sendPublishedGroupAnnouncementPush($groupId, $announcementId): void
    {
        $group = $this->entityManager
            ->getRepository('CivixCoreBundle:Group')
            ->find($groupId);
        $announcement = $this->entityManager
            ->getRepository(GroupAnnouncement::class)
            ->find($announcementId);
        $lastId = 0;

        do {
            /** @var User[] $users */
            $users = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByGroupForLeaderContentPush(
                    $announcement,
                    self::TYPE_PUSH_ANNOUNCEMENT,
                    $lastId,
                    self::MAX_USERS_PER_QUERY
                );

            if ($users) {
                foreach ($users as $recipient) {
                    $this->send(
                        $recipient,
                        $group->getOfficialName(),
                        $this->preview($announcement->getContent()),
                        self::TYPE_PUSH_ANNOUNCEMENT,
                        [
                            'target' => [
                                'id' => $announcementId,
                                'type' => 'announcement-published',
                            ],
                        ],
                        $group->getAvatarFileName()
                    );
                    $lastId = $recipient->getId();
                }
            }

            $this->entityManager->clear();
        } while ($users);
    }

    /**
     * User is invited to join group.
     *
     * @param $userId
     * @param $groupId
     */
    public function sendGroupInvitePush($userId, $groupId): void
    {
        $user = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUserForPush($userId);
        $group = $this->entityManager
            ->getRepository('CivixCoreBundle:Group')
            ->find($groupId);
        
        if ($user instanceof User) {
            $this->send(
                $user,
                $group->getOfficialName(),
                self::INVITE_PUSH_MESSAGE,
                self::TYPE_PUSH_INVITE,
                [
                    'target' => [
                        'id' => $group->getId(),
                        'type' => 'invite-sent',
                    ],
                ],
                $group->getAvatarFileName()
            );
        }
    }

    public function sendSocialActivity($id): void
    {
        $socialActivity = $this->entityManager->getRepository(SocialActivity::class)->find($id);
        if (!$socialActivity) {
            $this->logger->error('Social activity is not found.', ['id' => $id]);
            return;
        }
        $handledIds = [];
        $target = $socialActivity->getTarget();
        // send to recipient
        if ($socialActivity->getRecipient()) {
            $user = $this->entityManager->getRepository('CivixCoreBundle:User')
                ->getUserForPush($socialActivity->getRecipient()->getId());
            if ($user) {
                $handledIds[] = $user->getId();
                $this->send(
                    $user,
                    $socialActivity->getTitle(),
                    $socialActivity->getTextMessage(),
                    $socialActivity->getType(),
                    ['id' => $socialActivity->getId(), 'target' => $target],
                    $socialActivity->getImage()
                );
            }
        // send to followers
        } elseif (
            $socialActivity->getFollowing()
            && !in_array($socialActivity->getType(), [
                SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
                SocialActivity::TYPE_FOLLOW_POST_COMMENTED,
                SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
            ], true)
        ) {
            /** @var User[] $recipients */
            $recipients = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByFollowingForPush($socialActivity->getFollowing());
            $userGroupRepository = $this->entityManager->getRepository('CivixCoreBundle:UserGroup');
            foreach ($recipients as $recipient) {
                $handledIds[] = $recipient->getId();
                if (!$socialActivity->getGroup() ||
                    $userGroupRepository->isJoinedUser($socialActivity->getGroup(), $recipient)) {
                    $this->send(
                        $recipient,
                        $socialActivity->getTitle(),
                        $socialActivity->getTextMessage(),
                        $socialActivity->getType(),
                        ['id' => $socialActivity->getId(), 'target' => $target],
                        $socialActivity->getImage()
                    );
                }
            }
        // send to subscribers
        } else {
            $this->sendCommentedPush($socialActivity, $handledIds);
        }
    }

    public function sendCommentedPush(SocialActivity $socialActivity, array $handledIds = []): void
    {
        $target = $socialActivity->getTarget();
        if (!isset($target['id'])) {
            return;
        }
        switch ($socialActivity->getType()) {
            case SocialActivity::TYPE_FOLLOW_POLL_COMMENTED:
                $repo = $this->entityManager->getRepository(Question::class);
                break;
            case SocialActivity::TYPE_FOLLOW_POST_COMMENTED:
                $repo = $this->entityManager->getRepository(Post::class);
                break;
            case SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED:
                $repo = $this->entityManager->getRepository(UserPetition::class);
                break;
            default:
                return;
        }
        if (!$subscription = $repo->find($target['id'])) {
            return;
        }
        $subscribers = $this->entityManager->getRepository(User::class)
            ->getSubscribersIterator($subscription);
        foreach ($subscribers as $subscriber) {
            /** @var User $recipient */
            $recipient = $subscriber[0];
            // user is already handled or is an owner of the subscription (use OWN_*ENTITY*_COMMENTED event)
            if (in_array($recipient->getId(), $handledIds, true) || $recipient->isEqualTo($subscription->getUser())) {
                continue;
            }
            $this->send(
                $recipient,
                $socialActivity->getTitle(),
                $socialActivity->getTextMessage(),
                $socialActivity->getType(),
                ['id' => $socialActivity->getId(), 'target' => $target],
                $socialActivity->getImage()
            );
        }
    }

    public function send(User $recipient, $title, $message, $type, $entityData = null, $image = null): void
    {
        $message = new PushMessage($recipient, $title, $message, $type, $entityData, $image);
        try {
            $this->sender->send($message);
        } catch (\Exception $e) {
            $this->logger->error('Push error: '.$e->getMessage(), $e->getTrace());
        }
    }

    private function preview(string $text): string
    {
        if (mb_strlen($text) > 300) {
            return mb_substr($text, 0, 300) . '...';
        }

        return $text;
    }
}

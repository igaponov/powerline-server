<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Announcement\GroupAnnouncement;
use Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Notification\AbstractEndpoint;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\Poll\QuestionUserPush;
use Doctrine\ORM\EntityManager;
use Imgix\UrlBuilder;
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
    /*Not used in push notification but reserved and use in settings*/
    const TYPE_PUSH_PETITION = 'petition';
    const TYPE_PUSH_NEWS = 'leader_news';
    const TYPE_PUSH_EVENT = 'leader_event';
    const TYPE_PUSH_SOCIAL_ACTIVITY = 'social_activity';

    const MAX_USERS_PER_QUERY = 5000;

    const IMAGE_WIDTH = 320;
    const IMAGE_HEIGHT = 400;
    const IMAGE_LINK = '/bundles/civixfront/img/logo_320x320.jpg';
    const IMAGE_PATH = 'avatars';

    protected $entityManager;
    protected $questionUsersPush;
    protected $notification;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;
    /**
     * @var string
     */
    private $hostname;

    public function __construct(
        EntityManager $entityManager,
        QuestionUserPush $questionUsersPush,
        Notification $notification,
        LoggerInterface $logger,
        UrlBuilder $urlBuilder,
        $hostname
    ) {
        $this->entityManager = $entityManager;
        $this->questionUsersPush = $questionUsersPush;
        $this->notification = $notification;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->hostname = $hostname;
    }

    /**
     * Leader publishes poll, petition, discussion, fundraiser, event (all group members notified)
     *
     * @param $questionId
     * @param $title
     * @param $message
     */
    public function sendPushPublishQuestion($questionId, $title, $message)
    {
        /** @var Question $question */
        $question = $this->entityManager
            ->getRepository('CivixCoreBundle:Poll\Question')
            ->find($questionId);

        if (!$question) {
            return;
        }

        $avatar = $this->getLinkByFilename($question->getGroup()->getAvatarFileName());

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
    public function sendBoostedPetitionPush($groupId, $petitionId = null)
    {
        $users = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByGroupForPush($groupId, self::TYPE_PUSH_PETITION);

        $petition = $this->entityManager->getRepository(UserPetition::class)->find($petitionId);

        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                sprintf(
                    "%s in %s",
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
                $this->getLinkByFilename($petition->getUser()->getAvatarFileName())
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
    public function sendBoostedPostPush($groupId, $postId = null)
    {
        $users = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByGroupForPush($groupId, self::TYPE_PUSH_PETITION);

        $post = $this->entityManager->getRepository(Post::class)->find($postId);

        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                sprintf(
                    "%s in %s",
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
                $this->getLinkByFilename($post->getUser()->getAvatarFileName())
            );
        }
    }

    public function sendPublishedRepresentativeAnnouncementPush($representativeId, $announcementId)
    {
        /** @var Representative $representative */
        $representative = $this->entityManager
            ->getRepository('CivixCoreBundle:Representative')
            ->find($representativeId);

        if (!$representative) {
            return;
        }
        $users = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUsersByDistrictForPush($representative->getDistrictId(), self::TYPE_PUSH_ANNOUNCEMENT);
        $announcement = $this->entityManager
            ->getRepository(RepresentativeAnnouncement::class)
            ->find($announcementId);
        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                $representative->getOfficialName(),
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
    public function sendPublishedGroupAnnouncementPush($groupId, $announcementId)
    {
        $users = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUsersByGroupForPush($groupId, self::TYPE_PUSH_ANNOUNCEMENT);
        $group = $this->entityManager
            ->getRepository('CivixCoreBundle:Group')
            ->find($groupId);
        $announcement = $this->entityManager
            ->getRepository(GroupAnnouncement::class)
            ->find($announcementId);
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
                $this->getLinkByFilename($group->getAvatarFileName())
            );
        }
    }

    /**
     * User is invited to join group.
     *
     * @param $userId
     * @param $groupId
     */
    public function sendGroupInvitePush($userId, $groupId)
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
                $this->getLinkByFilename($group->getAvatarFileName())
            );
        }
    }

    public function sendSocialActivity($id)
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
                    $this->getLinkByFilename($socialActivity->getImage())
                );
            }
        // send to followers
        } elseif ($socialActivity->getFollowing()) {
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
                        $this->getLinkByFilename($socialActivity->getImage())
                    );
                }
            }
        // send to subscribers
        } else {
            $this->sendCommentedPush($socialActivity, $handledIds);
        }
    }

    public function sendCommentedPush(SocialActivity $socialActivity, $handledIds = [])
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
            if (in_array($recipient->getId(), $handledIds) || $recipient->isEqualTo($subscription->getUser())) {
                continue;
            }
            $this->send(
                $recipient,
                $socialActivity->getTitle(),
                $socialActivity->getTextMessage(),
                $socialActivity->getType(),
                ['id' => $socialActivity->getId(), 'target' => $target],
                $this->getLinkByFilename($socialActivity->getImage())
            );
        }
    }

    public function send(User $recipient, $title, $message, $type, $entityData = null, $image = null)
    {
        /** @var AbstractEndpoint[] $endpoints */
        $endpoints = $this->entityManager->getRepository(AbstractEndpoint::class)->findBy(['user' => $recipient]);
        if (empty($image)) {
            $image = 'https://'.$this->hostname.self::IMAGE_LINK;
        }
        $badge = $this->getBadge($recipient);
        foreach ($endpoints as $endpoint) {
            try {
                $this->notification->send(
                    $title,
                    $message,
                    $type,
                    $entityData,
                    $image,
                    $endpoint,
                    $badge
                );
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }
        }
    }

    private function preview($text)
    {
        if (mb_strlen($text) > 300) {
            return mb_substr($text, 0, 300) . '...';
        }

        return $text;
    }

    private function getLinkByFilename($fileName)
    {
        if (!$fileName) {
            return null;
        }

        return $this->urlBuilder->createURL(
            self::IMAGE_PATH.'/'.$fileName,
            array("dpr" => 0.75, "w" => self::IMAGE_WIDTH, "h" => self::IMAGE_HEIGHT)
        );
    }

    private function getBadge(User $user)
    {
        return $this->entityManager->getRepository(Activity::class)
            ->countPriorityActivitiesByUser($user, new \DateTime('-30 days'));
    }
}

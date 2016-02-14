<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Micropetitions\Petition as Micropetition;
use Civix\CoreBundle\Entity\Notification\AbstractEndpoint;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\Poll\QuestionUserPush;
use Doctrine\ORM\EntityManager;
use Imgix\UrlBuilder;
use Symfony\Bridge\Monolog\Logger;

class PushSender
{
    const QUESTION_PUSH_MESSAGE = 'New question has been published';
    const INVITE_PUSH_MESSAGE = 'You have been invited to join this group';
    const INFLUENCE_PUSH_MESSAGE = 'You got new followers';
    const ANNOUNCEMENT_PUSH_MESSAGE = 'New announcement has been published';
    const NEWS_PUSH_MESSAGE = 'New discussion has been published';
    const PAYMENT_REQUEST_PUSH_MESSAGE = 'New Payment Request';
    const EVENT_PUSH_MESSAGE = 'New event has been published';

    const TYPE_PUSH_ACTIVITY = 'activity';
    const TYPE_PUSH_ANNOUNCEMENT = 'announcement';
    const TYPE_PUSH_INFLUENCE = 'influence';
    const TYPE_PUSH_INVITE = 'invite';
    const TYPE_PUSH_MICRO_PETITION = 'micro_petition';
    /*Not used in push notification but reserved and use in settings*/
    const TYPE_PUSH_PETITION = 'petition';
    const TYPE_PUSH_NEWS = 'leader_news';
    const TYPE_PUSH_EVENT = 'leader_event';
    const TYPE_PUSH_SOCIAL_ACTIVITY = 'social_activity';
    
    const MAX_USERS_PER_QUERY = 5000;

    const IMAGE_WIDTH = 320;
    const IMAGE_HEIGHT = 400;
    const IMAGE_LINK = 'www/images/notification_image.jpg';

    protected $entityManager;
    protected $questionUsersPush;
    protected $notification;
    protected $logger;
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    public function __construct(
        EntityManager $entityManager,
        QuestionUserPush $questionUsersPush,
        Notification $notification,
        Logger $logger,
        UrlBuilder $urlBuilder
    ) {
        $this->entityManager = $entityManager;
        $this->questionUsersPush = $questionUsersPush;
        $this->notification = $notification;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    public function sendPushPublishQuestion($questionId, $title, $message)
    {
        /** @var Question $question */
        $question = $this->entityManager
            ->getRepository('CivixCoreBundle:Poll\Question')
            ->find($questionId);
        /** @var Petition $petition */
        $petition = $this->entityManager
            ->getRepository('CivixCoreBundle:Poll\Question\Petition')
            ->find($questionId);

        if (!$question || !$petition) {
            return;
        }

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
                            'id' => $question->getId(),
                        ]
                    );
                    $lastId = $recipient->getId();
                }
            }

            $this->entityManager->clear();

        } while ($users);
    }

    public function sendGroupPetitionPush($groupId, $microPetitionId = null)
    {
        $users = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByGroupForPush($groupId, self::TYPE_PUSH_PETITION);

        $microPetition = $this->entityManager->getRepository(Micropetition::class)->find($microPetitionId);

        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                sprintf(
                    "%s in %s",
                    $microPetition->getUser()
                        ->getFullName(),
                    $microPetition->getGroup()
                        ->getOfficialName()
                ),
                "Boosted: {$microPetition->getPetitionBody()}",
                self::TYPE_PUSH_MICRO_PETITION,
                ['id' => $microPetitionId],
                $this->getLinkByFilename($microPetition->getUser()->getAvatarFileName())
            );
        }
    }

    public function sendRepresentativeAnnouncementPush($representativeId, $message = self::ANNOUNCEMENT_PUSH_MESSAGE)
    {
        /** @var Representative $representative */
        $representative = $this->entityManager
            ->getRepository('CivixCoreBundle:Representative')
            ->findOneById($representativeId);

        if (!$representative) {
            return;
        }
        $users = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUsersByDistrictForPush($representative->getDistrictId(), self::TYPE_PUSH_ANNOUNCEMENT);
        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                $representative->getOfficialName(),
                $message,
                self::TYPE_PUSH_ANNOUNCEMENT
            );
        }
    }

    public function sendGroupAnnouncementPush($groupId, $message = self::ANNOUNCEMENT_PUSH_MESSAGE)
    {
        $users = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUsersByGroupForPush($groupId, self::TYPE_PUSH_ANNOUNCEMENT);
        $group = $this->entityManager
            ->getRepository('CivixCoreBundle:Group')
            ->find($groupId);
        foreach ($users as $recipient) {
            $this->send(
                $recipient,
                $group->getOfficialName(),
                $message,
                self::TYPE_PUSH_ANNOUNCEMENT
            );
        }
    }

    public function sendInvitePush($userId, $groupId)
    {
        $user = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUserForPush($userId);
        $group = $this->entityManager
            ->getRepository('CivixCoreBundle:Group')
            ->find($groupId);
        
        if ($user instanceof User) {
            $this->send($user, $group->getOfficialName(), self::INVITE_PUSH_MESSAGE, self::TYPE_PUSH_INVITE, null, $this->getLinkByFilename($group->getAvatarFileName()));
        }
    }

    public function sendInfluencePush($userId, $followerId = 0)
    {
        $user = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->getUserForPush($userId);

        $follower = $this->entityManager
            ->getRepository('CivixCoreBundle:User')
            ->find($followerId);

        if ($user instanceof User && $follower) {
            $this->send(
                $user,
                $follower->getFullName(),
                $follower->getFullName().' wants to follow you',
                self::TYPE_PUSH_INFLUENCE,
                null,
                $this->getLinkByFilename($follower->getAvatarFileName())
            );
        }
    }

    public function sendSocialActivity($id)
    {
        $socialActivity = $this->entityManager->getRepository(SocialActivity::class)->find($id);
        if ($socialActivity->getRecipient()) {
            $user = $this->entityManager->getRepository('CivixCoreBundle:User')
                ->getUserForPush($socialActivity->getRecipient()->getId());
            if ($user) {
                $this->send(
                    $user,
                    $socialActivity->getTitle(),
                    $socialActivity->getTextMessage(),
                    self::TYPE_PUSH_SOCIAL_ACTIVITY,
                    ['id' => $socialActivity->getId(), 'target' => $socialActivity->getTarget()],
                    $this->getLinkByFilename($socialActivity->getImage())
                );
            }
        } else if ($socialActivity->getFollowing()) {
            $recipients = $this->entityManager
                ->getRepository('CivixCoreBundle:User')
                ->getUsersByFollowingForPush($socialActivity->getFollowing());
            $userGroupRepository = $this->entityManager->getRepository('CivixCoreBundle:UserGroup');
            foreach ($recipients as $recipient) {
                if (!$socialActivity->getGroup() ||
                    $userGroupRepository->isJoinedUser($socialActivity->getGroup(), $recipient)) {
                    $this->send(
                        $recipient,
                        $socialActivity->getTitle(),
                        $socialActivity->getTextMessage(),
                        self::TYPE_PUSH_SOCIAL_ACTIVITY,
                        ['id' => $socialActivity->getId(), 'target' => $socialActivity->getTarget()],
                        $this->getLinkByFilename($socialActivity->getImage())
                    );
                }
            }
        }
    }
    
    public function send(User $recipient, $title, $message, $type, $entityData = null, $image = null)
    {
        $endpoints = $this->entityManager->getRepository(AbstractEndpoint::class)->findByUser($recipient);
        if (empty($image)) {
            $image = self::IMAGE_LINK;
        }
        foreach ($endpoints as $endpoint) {
            try {
                $this->notification->send(
                    $title,
                    $this->preview($message),
                    $type,
                    $entityData,
                    $image,
                    $endpoint
                );
            } catch (\Exception $e) {
                $this->logger->addError($e->getMessage());
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
        return $this->urlBuilder->createURL(
            $fileName,
            array("dpr" => 0.75, "w" => self::IMAGE_WIDTH, "h" => self::IMAGE_HEIGHT)
        );
    }
}

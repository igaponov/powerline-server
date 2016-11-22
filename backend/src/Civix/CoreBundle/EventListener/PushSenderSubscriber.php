<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Event;
use Civix\CoreBundle\Service\PushTask;
use Civix\CoreBundle\Service\QueueTaskInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushSenderSubscriber implements EventSubscriberInterface
{
    /**
     * @var PushTask
     */
    private $pushTask;

    public function __construct(QueueTaskInterface $pushTask)
    {
        $this->pushTask = $pushTask;
    }

    public static function getSubscribedEvents()
    {
        return [
            Event\AnnouncementEvents::PUBLISHED => ['sendAnnouncementPush', -200],
            Event\PollEvents::QUESTION_PUBLISHED => ['sendPushPublishQuestion', -200],
            Event\UserPetitionEvents::PETITION_BOOST => ['sendBoostedPetitionPush', -200],
            Event\PostEvents::POST_BOOST => ['sendBoostedPostPush', -200],
            Event\InviteEvents::CREATE => ['sendUserToGroupInvites', -200],
        ];
    }

    public function sendAnnouncementPush(Event\AnnouncementEvent $event)
    {
        $announcement = $event->getAnnouncement();
        if ($announcement instanceof RepresentativeAnnouncement) {
            $method = 'sendPublishedRepresentativeAnnouncementPush';
        } else {
            $method = 'sendPublishedGroupAnnouncementPush';
        }
        $this->pushTask->addToQueue($method, [
            $announcement->getRoot()->getId(),
            $announcement->getId(),
        ]);
    }

    public function sendPushPublishQuestion(Event\Poll\QuestionEvent $event)
    {
        $entity = $event->getQuestion();

        if ($entity instanceof Question\LeaderNews) {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Discussion",
                $this->preview($entity->getSubject()),
            ];
        } elseif ($entity instanceof Question\Petition) {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Petition",
                $entity->getPetitionTitle(),
            ];
        } elseif ($entity instanceof Question\PaymentRequest) {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Fundraiser",
                $entity->getTitle(),
            ];
        } elseif ($entity instanceof Question\LeaderEvent) {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Event",
                $entity->getTitle(),
            ];
        } else {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Poll",
                $this->preview($entity->getSubject()),
            ];
        }

        $this->pushTask->addToQueue('sendPushPublishQuestion', $params);
    }

    public function sendBoostedPetitionPush(Event\UserPetitionEvent $event)
    {
        $petition = $event->getPetition();
        $this->pushTask->addToQueue(
            'sendBoostedPetitionPush',
            [$petition->getGroup()->getId(), $petition->getId()]
        );
    }

    public function sendBoostedPostPush(Event\PostEvent $event)
    {
        $post = $event->getPost();
        $this->pushTask->addToQueue(
            'sendBoostedPostPush',
            [$post->getGroup()->getId(), $post->getId()]
        );
    }

    public function sendUserToGroupInvites(Event\InviteEvent $event)
    {
        $invite = $event->getInvite();
        $this->pushTask->addToQueue(
            'sendGroupInvitePush',
            [$invite->getUser()->getId(), $invite->getGroup()->getId()]
        );
    }

    private function preview($text)
    {
        if (mb_strlen($text) > 300) {
            return mb_substr($text, 0, 300) . '...';
        }

        return $text;
    }
}
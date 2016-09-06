<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Event;
use Civix\CoreBundle\Service\PushTask;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushSenderSubscriber implements EventSubscriberInterface
{
    /**
     * @var PushTask
     */
    private $pushTask;

    public function __construct(PushTask $pushTask)
    {
        $this->pushTask = $pushTask;
    }

    public static function getSubscribedEvents()
    {
        return [
            Event\AnnouncementEvents::PUBLISHED => ['sendAnnouncementPush', -200],
            Event\UserEvents::FOLLOWED => ['sendInfluencePush', -200],
            Event\PollEvents::QUESTION_PUBLISHED => ['sendPushPublishQuestion', -200],
            Event\UserPetitionEvents::PETITION_BOOST => ['sendGroupPetitionPush', -200],
            Event\PostEvents::POST_BOOST => ['sendGroupPostPush', -200],
            Event\InviteEvents::CREATE => ['sendUserToGroupInvites', -200],
        ];
    }

    public function sendAnnouncementPush(Event\AnnouncementEvent $event)
    {
        $announcement = $event->getAnnouncement();
        if ($announcement instanceof RepresentativeAnnouncement) {
            $method = 'sendRepresentativeAnnouncementPush';
        } else {
            $method = 'sendGroupAnnouncementPush';
        }
        $this->pushTask->addToQueue($method, [
            $announcement->getUser()->getId(), 
            $announcement->getContent()
        ]);
    }

    public function sendInfluencePush(Event\UserFollowEvent $event)
    {
        $follow = $event->getUserFollow();
        $this->pushTask->addToQueue('sendInfluencePush', [
            $follow->getUser()->getId(),
            $follow->getFollower()->getId(),
        ]);
    }

    public function sendPushPublishQuestion(Event\Poll\QuestionEvent $event)
    {
        $entity = $event->getQuestion();

        if ($entity instanceof Question\LeaderNews) {
            $params = [
                $entity->getId(),
                "Discuss: {$entity->getSubject()}",
            ];
        } elseif ($entity instanceof Question\Petition) {
            $params = [
                $entity->getId(),
                "Sign: {$entity->getPetitionTitle()}",
                "Sign: {$entity->getPetitionBody()}",
            ];
        } elseif ($entity instanceof Question\PaymentRequest) {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Fundraiser",
                $entity->getTitle()
            ];
        } elseif ($entity instanceof Question\LeaderEvent) {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Event",
                "RSVP: {$entity->getTitle()}"
            ];
        } else {
            $params = [
                $entity->getId(),
                "{$entity->getOwner()->getOfficialName()} Poll",
                $entity->getSubject()
            ];
        }

        $this->pushTask->addToQueue('sendPushPublishQuestion', $params);
    }

    public function sendGroupPetitionPush(Event\UserPetitionEvent $event)
    {
        $petition = $event->getPetition();
        $this->pushTask->addToQueue(
            'sendGroupPetitionPush',
            [$petition->getGroup()->getId(), $petition->getId()]
        );
    }

    public function sendGroupPostPush(Event\PostEvent $event)
    {
        $post = $event->getPost();
        $this->pushTask->addToQueue(
            'sendGroupPostPush',
            [$post->getGroup()->getId(), $post->getId()]
        );
    }

    public function sendUserToGroupInvites(Event\InviteEvent $event)
    {
        $invite = $event->getInvite();
        $this->pushTask->addToQueue(
            'sendInvitePush',
            [$invite->getUser()->getId(), $invite->getGroup()->getId()]
        );
    }
}
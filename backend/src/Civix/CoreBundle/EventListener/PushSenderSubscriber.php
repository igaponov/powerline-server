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
            Event\AnnouncementEvents::PUBLISHED => 'sendAnnouncementPush',
            Event\UserEvents::FOLLOWED => 'sendInfluencePush',
            Event\PollEvents::QUESTION_PUBLISHED => 'sendPushPublishQuestion',
            Event\MicropetitionEvents::PETITION_BOOST => 'sendGroupPetitionPush',
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
                "{$entity->getUser()->getOfficialName()} Fundraiser",
                $entity->getTitle()
            ];
        } elseif ($entity instanceof Question\LeaderEvent) {
            $params = [
                $entity->getId(),
                "{$entity->getUser()->getOfficialName()} Event",
                "RSVP: {$entity->getTitle()}"
            ];
        } else {
            $params = [
                $entity->getId(),
                "{$entity->getUser()->getOfficialName()} Poll",
                $entity->getSubject()
            ];
        }

        $this->pushTask->addToQueue('sendPushPublishQuestion', $params);
    }

    public function sendGroupPetitionPush(Event\Micropetition\PetitionEvent $event)
    {
        $petition = $event->getPetition();
        $this->pushTask->addToQueue(
            'sendGroupPetitionPush',
            [$petition->getGroup()->getId(), $petition->getId()]
        );
    }
}
<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Event\Micropetition\AnswerEvent;
use Civix\CoreBundle\Event\Micropetition\PetitionEvent;
use Civix\CoreBundle\Event\MicropetitionEvents;
use Civix\CoreBundle\Event\Poll\QuestionEvent;
use Civix\CoreBundle\Event\PollEvents;
use Civix\CoreBundle\Service\ActivityUpdate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivityUpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @var ActivityUpdate
     */
    private $activityUpdate;

    public function __construct(ActivityUpdate $activityUpdate)
    {
        $this->activityUpdate = $activityUpdate;
    }

    public static function getSubscribedEvents()
    {
        return [
            MicropetitionEvents::PETITION_CREATE => 'publishMicroPetitionToActivity',
            MicropetitionEvents::PETITION_UPDATE => 'publishMicroPetitionToActivity',
            MicropetitionEvents::PETITION_SIGN => [
                ['updateResponsesPetition'],
                ['updateAuthorActivity'],
            ],
            MicropetitionEvents::PETITION_UNSIGN => 'updateAuthorActivity',
            MicropetitionEvents::PETITION_BOOST => 'publishMicroPetitionToActivity',
            PollEvents::QUESTION_PUBLISHED => 'publishQuestionToActivity',
        ];
    }

    public function publishMicroPetitionToActivity(PetitionEvent $event)
    {
        $this->activityUpdate->publishMicroPetitionToActivity($event->getPetition());
    }

    public function updateResponsesPetition(AnswerEvent $event)
    {
        $this->activityUpdate->updateResponsesPetition($event->getAnswer()->getPetition());
    }

    public function updateAuthorActivity(AnswerEvent $event)
    {
        $answer = $event->getAnswer();
        $this->activityUpdate->updateAuthorActivity($answer->getPetition(), $answer->getUser());
    }

    public function publishQuestionToActivity(QuestionEvent $event)
    {
        $question = $event->getQuestion();
        if ($question instanceof Petition) {
            $this->activityUpdate->publishPetitionToActivity($question);
        } elseif ($question instanceof PaymentRequest) {
            $this->activityUpdate->publishPaymentRequestToActivity($question);
        } elseif ($question instanceof LeaderNews) {
            $this->activityUpdate->publishLeaderNewsToActivity($question);
        } else {
            $this->activityUpdate->publishQuestionToActivity($question);
        }
    }
}
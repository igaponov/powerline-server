<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Event;
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
            Event\UserPetitionEvents::PETITION_CREATE => 'publishUserPetitionToActivity',
            Event\UserPetitionEvents::PETITION_UPDATE => 'publishUserPetitionToActivity',
            Event\UserPetitionEvents::PETITION_SIGN => [
                ['updateResponsesPetition'],
                ['updatePetitionAuthorActivity'],
            ],
            Event\UserPetitionEvents::PETITION_UNSIGN => 'updatePetitionAuthorActivity',
            Event\UserPetitionEvents::PETITION_BOOST => 'publishUserPetitionToActivity',

            Event\PostEvents::POST_CREATE => 'publishPostToActivity',
            Event\PostEvents::POST_UPDATE => 'publishPostToActivity',
            Event\PostEvents::POST_SIGN => [
                ['updateResponsesPost'],
                ['updatePostAuthorActivity'],
            ],
            Event\PostEvents::POST_UNSIGN => 'updatePostAuthorActivity',
            Event\PostEvents::POST_BOOST => 'publishPostToActivity',

            Event\PollEvents::QUESTION_PUBLISHED => 'publishQuestionToActivity',
        ];
    }

    public function publishUserPetitionToActivity(Event\UserPetitionEvent $event)
    {
        $this->activityUpdate->publishUserPetitionToActivity($event->getPetition());
    }

    public function publishPostToActivity(Event\PostEvent $event)
    {
        $this->activityUpdate->publishPostToActivity($event->getPost());
    }

    public function updateResponsesPetition(Event\UserPetition\SignatureEvent $event)
    {
        $this->activityUpdate->updateResponsesPetition($event->getSignature()->getPetition());
    }

    public function updateResponsesPost(Event\Post\AnswerEvent $event)
    {
        $this->activityUpdate->updateResponsesPost($event->getAnswer()->getPost());
    }

    public function updatePetitionAuthorActivity(Event\UserPetition\SignatureEvent $event)
    {
        $answer = $event->getSignature();
        $this->activityUpdate->updatePetitionAuthorActivity($answer->getPetition(), $answer->getUser());
    }

    public function updatePostAuthorActivity(Event\Post\AnswerEvent $event)
    {
        $answer = $event->getAnswer();
        $this->activityUpdate->updatePostAuthorActivity($answer->getPost(), $answer->getUser());
    }

    public function publishQuestionToActivity(Event\Poll\QuestionEvent $event)
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
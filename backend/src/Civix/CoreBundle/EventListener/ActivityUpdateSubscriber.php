<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\Micropetition\AnswerEvent;
use Civix\CoreBundle\Event\Micropetition\PetitionEvent;
use Civix\CoreBundle\Event\MicropetitionEvents;
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
}
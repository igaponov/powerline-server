<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Event\Micropetition\PetitionEvent;
use Civix\CoreBundle\Event\MicropetitionEvents;
use Civix\CoreBundle\Service\SocialActivityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SocialActivitySubscriber implements EventSubscriberInterface
{
    /**
     * @var SocialActivityManager
     */
    private $manager;

    public static function getSubscribedEvents()
    {
        return [
            MicropetitionEvents::PETITION_CREATE => 'noticeMicropetitionCreated',
        ];
    }

    public function __construct(SocialActivityManager $manager)
    {
        $this->manager = $manager;
    }

    public function noticeMicropetitionCreated(PetitionEvent $event)
    {
        $this->manager->noticeMicropetitionCreated($event->getPetition());
    }
}
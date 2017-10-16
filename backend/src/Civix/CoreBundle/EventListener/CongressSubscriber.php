<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\RepresentativeEvent;
use Civix\CoreBundle\Event\RepresentativeEvents;
use Civix\CoreBundle\Service\CongressApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CongressSubscriber implements EventSubscriberInterface
{
    /**
     * @var CongressApi
     */
    private $congressApi;

    public static function getSubscribedEvents(): array
    {
        return [
            RepresentativeEvents::UPDATE => 'updateRepresentativeProfile',
        ];
    }

    public function __construct(CongressApi $congressApi)
    {
        $this->congressApi = $congressApi;
    }

    public function updateRepresentativeProfile(RepresentativeEvent $event): void
    {
        $this->congressApi->updateRepresentativeProfile($event->getRepresentative());
    }
}
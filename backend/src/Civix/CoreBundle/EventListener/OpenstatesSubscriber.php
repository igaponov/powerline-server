<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\RepresentativeEvent;
use Civix\CoreBundle\Event\RepresentativeEvents;
use Civix\CoreBundle\Service\OpenstatesApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OpenstatesSubscriber implements EventSubscriberInterface
{
    /**
     * @var OpenstatesApi
     */
    private $openstatesApi;

    public static function getSubscribedEvents(): array
    {
        return [
            RepresentativeEvents::UPDATE => 'updateRepresentativeProfile',
        ];
    }

    public function __construct(OpenstatesApi $openstatesApi)
    {
        $this->openstatesApi = $openstatesApi;
    }

    public function updateRepresentativeProfile(RepresentativeEvent $event): void
    {
        $this->openstatesApi->updateRepresentativeProfile($event->getRepresentative());
    }
}
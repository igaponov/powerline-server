<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\CiceroRepresentativeEvent;
use Civix\CoreBundle\Event\CiceroRepresentativeEvents;
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
            CiceroRepresentativeEvents::UPDATE => 'updateRepresentativeProfile',
        ];
    }

    public function __construct(OpenstatesApi $openstatesApi)
    {
        $this->openstatesApi = $openstatesApi;
    }

    public function updateRepresentativeProfile(CiceroRepresentativeEvent $event): void
    {
        $this->openstatesApi->updateRepresentativeProfile($event->getRepresentative());
    }
}
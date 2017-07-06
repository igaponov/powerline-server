<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\CiceroRepresentativeEvent;
use Civix\CoreBundle\Event\CiceroRepresentativeEvents;
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
            CiceroRepresentativeEvents::UPDATE => 'updateRepresentativeProfile',
        ];
    }

    public function __construct(CongressApi $congressApi)
    {
        $this->congressApi = $congressApi;
    }

    public function updateRepresentativeProfile(CiceroRepresentativeEvent $event): void
    {
        $this->congressApi->updateRepresentativeProfile($event->getRepresentative());
    }
}
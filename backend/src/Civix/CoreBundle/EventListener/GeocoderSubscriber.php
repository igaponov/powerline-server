<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeocoderSubscriber implements EventSubscriberInterface
{
    /**
     * @var Geocoder
     */
    private $geocoder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::REGISTRATION => 'setCoordinates',
            UserEvents::LEGACY_REGISTRATION => 'setCoordinates',
        ];
    }

    public function __construct(Geocoder $geocoder, LoggerInterface $logger)
    {
        $this->geocoder = $geocoder;
        $this->logger = $logger;
    }

    public function setCoordinates(UserEvent $event): void
    {
        $user = $event->getUser();
        $query = $user->getAddressQuery();

        try {
            $collection = $this->geocoder->geocode($query);
        } catch (Exception $e) {
            $this->logger->critical('Geocoder error has occurred.', [
                'query' => $query,
                'exception' => $e,
            ]);
            return;
        }
        if ($collection->count()) {
            $address = $collection->first();
            $coordinates = $address->getCoordinates();
            $user->setLatitude($coordinates->getLatitude());
            $user->setLongitude($coordinates->getLongitude());
        }
    }
}
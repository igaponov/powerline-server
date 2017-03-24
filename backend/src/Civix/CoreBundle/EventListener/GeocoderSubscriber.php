<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Geocoder\Geocoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeocoderSubscriber implements EventSubscriberInterface
{
    /**
     * @var Geocoder
     */
    private $geocoder;

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::REGISTRATION => 'setCoordinates',
        ];
    }

    public function __construct(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }

    public function setCoordinates(UserEvent $event)
    {
        $user = $event->getUser();
        $query = $user->getAddressQuery();

        $collection = $this->geocoder->geocode($query);
        if ($collection->count()) {
            $address = $collection->first();
            $coordinates = $address->getCoordinates();
            $user->setLatitude($coordinates->getLatitude());
            $user->setLongitude($coordinates->getLongitude());
        }
    }
}
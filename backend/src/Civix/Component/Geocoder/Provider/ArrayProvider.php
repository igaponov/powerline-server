<?php

namespace Civix\Component\Geocoder\Provider;

use Geocoder\Exception\NoResult;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Bounds;
use Geocoder\Model\Coordinates;
use Geocoder\Model\Country;
use Geocoder\Provider\Provider;

class ArrayProvider implements Provider
{
    /**
     * @var int
     */
    private $limit;
    /**
     * @var AddressCollection[]
     */
    private $addressCollections;

    public function __construct(array $addressCollections = [])
    {
        foreach ($addressCollections as $query => $addressCollection) {
            $this->addressCollections[$query] = new AddressCollection(
                array_map(function ($address) {
                    return new Address(
                        isset($address['coordinates']) ?
                            new Coordinates(
                                $address['coordinates']['latitude'] ?? 0,
                                $address['coordinates']['longitude'] ?? 0
                            ) : null,
                        isset($address['bounds']) ?
                            new Bounds(
                                $address['bounds']['south'] ?? 0,
                                $address['bounds']['west'] ?? 0,
                                $address['bounds']['north'] ?? 0,
                                $address['bounds']['east'] ?? 0
                            ) : null,
                        $address['streetNumber'] ?? null,
                        $address['streetName'] ?? null,
                        $address['postalCode'] ?? null,
                        $address['locality'] ?? null,
                        $address['subLocality'] ?? null,
                        !empty($address['adminLevels']) ?
                            new AdminLevelCollection(
                                array_map(function ($adminLevel, $level) {
                                    return new AdminLevel(
                                        $level,
                                        $adminLevel['name'] ?? '',
                                        $adminLevel['code'] ?? ''
                                    );
                                },
                                    $address['adminLevels'],
                                    range(1, count($address['adminLevels']))
                                )
                            ) : null,
                        isset($address['country']) ?
                            new Country(
                                $address['country']['name'] ?? '',
                                $address['country']['code'] ?? ''
                            ) : null,
                        $address['timezone'] ?? null
                    );
                }, $addressCollection)
            );
        }
    }

    public function geocode($value)
    {
        if (isset($this->addressCollections[$value])) {
            return $this->addressCollections[$value];
        } else {
            throw new NoResult();
        }
    }

    public function reverse($latitude, $longitude)
    {
        foreach ($this->addressCollections as $addressCollection) {
            foreach ($addressCollection as $address) {
                /** @var Coordinates $coordinates */
                if ($coordinates = $address->getCoordinates() && $coordinates->getLatitude() == $latitude && $coordinates->getLongitude() == $longitude) {
                    return $addressCollection;
                }
            }
        }
        throw new NoResult();
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function getName()
    {
        return 'array';
    }
}
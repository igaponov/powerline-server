<?php

namespace Tests\Civix\Component\Geocoder\Provider;

use Civix\Component\Geocoder\Provider\ArrayProvider;
use Geocoder\Model\AddressCollection;
use PHPUnit\Framework\TestCase;

class ArrayProviderTest extends TestCase
{
    public function testGeocodeIsOk()
    {
        $query = 'example query';
        $provider = new ArrayProvider([$query => []]);
        $result = $provider->geocode($query);
        $this->assertInstanceOf(AddressCollection::class, $result);
    }

    /**
     * @expectedException \Geocoder\Exception\NoResult
     */
    public function testGeocodeWithNoResults()
    {
        $provider = new ArrayProvider();
        $provider->geocode('example query');
    }
}
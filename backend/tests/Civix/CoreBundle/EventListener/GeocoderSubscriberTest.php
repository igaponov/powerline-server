<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\EventListener\GeocoderSubscriber;
use Faker\Factory;
use Geocoder\Exception\NoResult;
use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\Coordinates;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GeocoderSubscriberTest extends TestCase
{
    public function testSetCoordinates(): void
    {
        $faker = Factory::create();
        $user = new User();
        $user->setAddress1($faker->address)
            ->setCity($faker->city)
            ->setState($faker->toUpper($faker->lexify('??')))
            ->setCountry($faker->country);
        $latitude = $faker->latitude;
        $longitude = $faker->longitude;
        $collection = new AddressCollection(
            [new Address(new Coordinates($latitude, $longitude))]
        );
        $geocoder = $this->createMock(Geocoder::class);
        $geocoder->expects($this->once())
            ->method('geocode')
            ->with($user->getAddressQuery())
            ->willReturn($collection);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');
        $subscriber = new GeocoderSubscriber($geocoder, $logger);
        $event = new UserEvent($user);
        $subscriber->setCoordinates($event);
        $this->assertSame($latitude, $user->getLatitude());
        $this->assertSame($longitude, $user->getLongitude());
    }

    public function testSetCoordinatesThrowsExceptionOnInvalidAddress(): void
    {
        $user = new User();
        $geocoder = $this->createMock(Geocoder::class);
        $exception = new NoResult();
        $geocoder->expects($this->once())
            ->method('geocode')
            ->with($user->getAddressQuery())
            ->willThrowException($exception);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Geocoder error has occurred.', [
                'query' => $user->getAddressQuery(),
                'exception' => $exception,
            ]);
        $subscriber = new GeocoderSubscriber($geocoder, $logger);
        $event = new UserEvent($user);
        $subscriber->setCoordinates($event);
        $this->assertNull($user->getLatitude());
        $this->assertNull($user->getLongitude());
    }
}

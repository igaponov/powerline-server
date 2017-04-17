<?php

namespace Civix\CoreBundle\Tests\EventListener;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadLocalGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserLocalGroupData;
use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;

class UserLocalGroupSubscriberTest extends WebTestCase
{
    public function testCreateAllNewGroups()
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $groups = $this->handleEvent($this->getUSAddressCollection(), $user);
        $this->assertCount(3, $groups);
        $this->assertEquals('US', $groups[1]->getAcronym());
        $this->assertEquals('KS', $groups[2]->getAcronym());
        $this->assertEquals('Bucklin', $groups[3]->getOfficialName());
    }

    public function testCreateAllNewGroupsWithEU()
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $groups = $this->handleEvent($this->getEUAddressCollection(), $user);
        $this->assertCount(4, $groups);
        $this->assertEquals('EU', $groups[1]->getAcronym());
        $this->assertEquals('ES', $groups[2]->getAcronym());
        $this->assertEquals('Comunidad de Madrid', $groups[3]->getAcronym());
        $this->assertEquals('Madrid', $groups[4]->getOfficialName());
    }

    public function testCreateAllNewGroupsWithAU()
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $groups = $this->handleEvent($this->getAUAddressCollection(), $user);
        $this->assertCount(4, $groups);
        $this->assertEquals('AFU', $groups[1]->getAcronym());
        $this->assertEquals('EG', $groups[2]->getAcronym());
        $this->assertEquals('Cairo Governorate', $groups[3]->getAcronym());
        $this->assertEquals('Cairo', $groups[4]->getOfficialName());
    }

    public function testCreateExistentGroups()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadLocalGroupData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $us = $repository->getReference('local_group_us');
        $ks = $repository->getReference('local_group_ks');
        $bu = $repository->getReference('local_group_bu');
        $groups = $this->handleEvent($this->getUSAddressCollection(), $user);
        $this->assertCount(3, $groups);
        $this->assertEquals($us->getId(), $groups[1]->getId());
        $this->assertEquals($ks->getId(), $groups[2]->getId());
        $this->assertEquals($bu->getId(), $groups[3]->getId());
    }

    public function testUpdateExistentGroups()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserLocalGroupData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $us = $repository->getReference('local_group_us');
        $ks = $repository->getReference('local_group_ks');
        $bu = $repository->getReference('local_group_bu');
        $groups = $this->handleEvent($this->getUSAddressCollection(), $user);
        $this->assertCount(3, $groups);
        $this->assertEquals($us->getId(), $groups[1]->getId());
        $this->assertEquals($ks->getId(), $groups[2]->getId());
        $this->assertEquals($bu->getId(), $groups[3]->getId());
    }

    public function testUpdateExistentGroupsWithEUEmptyLocality()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserLocalGroupData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $eu = $repository->getReference('local_group_eu');
        $es = $repository->getReference('local_group_es');
        $cm = $repository->getReference('local_group_cm');
        $groups = $this->handleEvent(new AddressCollection(
            [
                new Address(
                    null, null, null, null, null, null, null, new AdminLevelCollection(
                    [
                        new AdminLevel(1, 'Comunidad de Madrid', 'Comunidad de Madrid'),
                        new AdminLevel(2, 'Madrid', 'M'),
                    ]
                ), new Country('Spain', 'ES')
                ),
                new Address(),
            ]
        ), $user);
        $this->assertCount(3, $groups);
        $this->assertEquals($eu->getId(), $groups[4]->getId());
        $this->assertEquals($es->getId(), $groups[5]->getId());
        $this->assertEquals($cm->getId(), $groups[6]->getId());
    }

    public function testUpdateExistentGroupsWithAUChangedLocality()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserLocalGroupData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_3');
        $au = $repository->getReference('local_group_au');
        $eg = $repository->getReference('local_group_eg');
        $cg = $repository->getReference('local_group_cg');
        $sh = $repository->getReference('local_group_sh');
        $collection = new AddressCollection(
            [
                new Address(
                    null, null, null, null, null, null, 'El Shorouk City', new AdminLevelCollection(
                    [
                        new AdminLevel(1, 'Cairo Governorate', 'Cairo Governorate'),
                    ]
                ), new Country('Egypt', 'EG')
                ),
                new Address(),
            ]
        );
        $groups = $this->handleEvent($collection, $user);
        $this->assertCount(4, $groups);
        $this->assertEquals($au->getId(), $groups[8]->getId());
        $this->assertEquals($eg->getId(), $groups[9]->getId());
        $this->assertEquals($cg->getId(), $groups[10]->getId());
        $this->assertEquals($sh->getId(), $groups[12]->getId());
    }

    /**
     * @param AddressCollection $collection
     * @param User $user
     * @return \Civix\CoreBundle\Entity\Group[]
     */
    private function handleEvent(AddressCollection $collection, User $user)
    {
        $geocoder = $this->createMock(Geocoder::class);
        $geocoder->expects($this->once())
            ->method('geocode')
            ->willReturn($collection);
        $container = $this->getContainer();
        $container->set('bazinga_geocoder.geocoder', $geocoder);
        $event = new UserEvent($user);
        $container
            ->get('civix_core.event_listener.user_local_group_subscriber')
            ->joinLocalGroups($event);
        return $container
            ->get('civix_core.repository.group_repository')
            ->getGeoGroupsByUser($user);
    }

    /**
     * @return AddressCollection
     */
    private function getUSAddressCollection(): AddressCollection
    {
        return new AddressCollection(
            [
                new Address(
                    null, null, null, null, '67834', 'Bucklin', null, new AdminLevelCollection(
                        [
                            new AdminLevel(1, 'Kansas', 'KS'),
                            new AdminLevel(2, 'Ford County', 'Ford County'),
                            new AdminLevel(3, 'Bucklin', 'Bucklin'),
                        ]
                    ), new Country('United States', 'US')
                ),
                new Address(),
            ]
        );
    }

    /**
     * @return AddressCollection
     */
    private function getEUAddressCollection(): AddressCollection
    {
        return new AddressCollection(
            [
                new Address(
                    null, null, null, null, '28071', 'Madrid', null, new AdminLevelCollection(
                        [
                            new AdminLevel(1, 'Comunidad de Madrid', 'Comunidad de Madrid'),
                            new AdminLevel(2, 'Madrid', 'M'),
                        ]
                    ), new Country('Spain', 'ES')
                ),
                new Address(),
            ]
        );
    }

    /**
     * @return AddressCollection
     */
    private function getAUAddressCollection(): AddressCollection
    {
        return new AddressCollection(
            [
                new Address(
                    null, null, null, null, null, 'Cairo', null, new AdminLevelCollection(
                        [
                            new AdminLevel(1, 'Cairo Governorate', 'Cairo Governorate'),
                        ]
                    ), new Country('Egypt', 'EG')
                ),
                new Address(),
            ]
        );
    }
}
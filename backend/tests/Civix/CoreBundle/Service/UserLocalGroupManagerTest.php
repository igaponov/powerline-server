<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Service\UserLocalGroupManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadLocalGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserLocalGroupData;
use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserLocalGroupManagerTest extends WebTestCase
{
    public function testCreateAllNewGroups(): void
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $this->join($this->getUSAddressCollection(), $user, 3);
        $groups = $user->getGroups();
        $this->assertCount(3, $groups);
        $this->assertEquals('US', $groups[0]->getAcronym());
        $this->assertEquals('KS', $groups[1]->getAcronym());
        $this->assertEquals('Bucklin', $groups[2]->getOfficialName());
    }

    public function testCreateAllNewGroupsWithEU(): void
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $this->join($this->getEUAddressCollection(), $user, 4);
        $groups = $user->getGroups();
        $this->assertCount(4, $groups);
        $this->assertEquals('EU', $groups[0]->getAcronym());
        $this->assertEquals('ES', $groups[1]->getAcronym());
        $this->assertEquals('Comunidad de Madrid', $groups[2]->getAcronym());
        $this->assertEquals('Madrid', $groups[3]->getOfficialName());
    }

    public function testCreateAllNewGroupsWithAU(): void
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $this->join($this->getAUAddressCollection(), $user, 4);
        $groups = $user->getGroups();
        $this->assertCount(4, $groups);
        $this->assertEquals('AFU', $groups[0]->getAcronym());
        $this->assertEquals('EG', $groups[1]->getAcronym());
        $this->assertEquals('Cairo Governorate', $groups[2]->getAcronym());
        $this->assertEquals('Cairo', $groups[3]->getOfficialName());
    }

    public function testCreateExistentGroups(): void
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
        $this->join($this->getUSAddressCollection(), $user);
        $groups = $user->getGroups();
        $this->assertCount(3, $groups);
        $this->assertEquals($us->getId(), $groups[0]->getId());
        $this->assertEquals($ks->getId(), $groups[1]->getId());
        $this->assertEquals($bu->getId(), $groups[2]->getId());
    }

    public function testUpdateExistentGroups(): void
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
        $this->join($this->getUSAddressCollection(), $user);
        $groups = $user->getGroups();
        $this->assertCount(3, $groups);
        $this->assertEquals($us->getId(), $groups[0]->getId());
        $this->assertEquals($ks->getId(), $groups[1]->getId());
        $this->assertEquals($bu->getId(), $groups[2]->getId());
    }

    public function testUpdateExistentGroupsWithEUEmptyLocality(): void
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
        $this->join(new AddressCollection(
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
        $groups = $user->getGroups();
        $this->assertCount(3, $groups);
        $this->assertEquals($eu->getId(), $groups[0]->getId());
        $this->assertEquals($es->getId(), $groups[1]->getId());
        $this->assertEquals($cm->getId(), $groups[2]->getId());
    }

    public function testUpdateExistentGroupsWithAUChangedLocality(): void
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
        $this->join($collection, $user);
        $groups = $user->getGroups();
        $this->assertCount(4, $groups);
        $this->assertEquals($au->getId(), $groups[0]->getId());
        $this->assertEquals($eg->getId(), $groups[1]->getId());
        $this->assertEquals($cg->getId(), $groups[2]->getId());
        $this->assertEquals($sh->getId(), $groups[3]->getId());
    }

    /**
     * @param AddressCollection $collection
     * @param User $user
     * @param int $newGroupCount
     */
    private function join(AddressCollection $collection, User $user, int $newGroupCount = 0): void
    {
        $geocoder = $this->createMock(Geocoder::class);
        $geocoder->expects($this->once())
            ->method('geocode')
            ->willReturn($collection);
        $container = $this->getContainer();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly($newGroupCount))
            ->method('dispatch')
            ->with(AvatarEvents::CHANGE, $this->isInstanceOf(AvatarEvent::class));
        $logger = $this->createMock(LoggerInterface::class);
        $manager = new UserLocalGroupManager(
            $geocoder,
            $container->get('doctrine.orm.entity_manager'),
            $container->get('civix_core.repository.group_repository'),
            $dispatcher,
            $logger
        );
        $manager->joinLocalGroups($user);
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

<?php

namespace Tests\Civix\CoreBundle\DataFixtures\ORM;

use Civix\Component\Notification\Model\Device;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDeviceData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_1');

        $device = new Device($user);
        $device->setId('ffffb794-ba37-11e3-8077-031d62f86ebf')
            ->setIdentifier('ce777617da7f548fe7a9ab6febb56cf39fb')
            ->setTimezone(-28800)
            ->setVersion('1.0')
            ->setOs('7.0.4')
            ->setModel('iPhone 8,2')
            ->setType(Device::TYPE_IOS);
        $manager->persist($device);
        $this->addReference('device_1', $device);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
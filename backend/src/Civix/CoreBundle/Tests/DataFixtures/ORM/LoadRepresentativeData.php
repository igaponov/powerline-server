<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\Representative;
use Faker\Factory;

class LoadRepresentativeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $user = $this->getReference('user_1');
        $group = $this->getReference('group_1');
        $representative = new Representative($user, $group);
        $representative->setOfficialTitle('Vice President');
        $representative->setCity('Los Angeles');
        $representative->setAddressLine1('6153 Smokey Ln');
        $representative->setPhone('(672)-586-7816');
        $representative->setPrivatePhone($faker->phoneNumber);
        $representative->setEmail('josephb26@example.com');
        $representative->setPrivateEmail($faker->companyEmail);
        $representative->setDistrict($this->getReference('district_us'));
        $representative->setIsNonLegislative(true);
        $this->addReference('representative_jb', $representative);
        $manager->persist($representative);

        $user = $this->getReference('user_2');
        $group = $this->getReference('group_2');
        $representative = new Representative($user, $group);
        $representative->setOfficialTitle('CEO');
        $representative->setCity('San Francisco');
        $representative->setAddressLine1('4143 Depaul Dr');
        $representative->setPhone('(385)-369-5636');
        $representative->setPrivatePhone($faker->phoneNumber);
        $representative->setEmail('jeanne.torres49@example.com');
        $representative->setPrivateEmail($faker->companyEmail);
        $representative->setDistrict($this->getReference('district_sf'));
        $representative->setUpdatedAt(new \DateTime('+1 week'));
        $this->addReference('representative_jt', $representative);
        $manager->persist($representative);

        $user = $this->getReference('user_3');
        $group = $this->getReference('group_3');
        $representative = new Representative($user, $group);
        $representative->setOfficialTitle('Software Engineer');
        $representative->setCity('San Diego');
        $representative->setAddressLine1('1730 Auerbach Ave');
        $representative->setPhone('(731)-567-8228');
        $representative->setPrivatePhone($faker->phoneNumber);
        $representative->setEmail('willie.carroll20@example.com');
        $representative->setPrivateEmail($faker->companyEmail);
        $representative->setDistrict($this->getReference('district_sd'));
        $representative->setIsNonLegislative(true);
        $representative->setUpdatedAt(new \DateTime('+1 week'));
        $this->addReference('representative_wc', $representative);
        $manager->persist($representative);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}

<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Entity\User;
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

        /** @var User $user */
        $user = $this->getReference('user_1');
        /** @var District $district */
        $district = $this->getReference('district_us');
        $representative = new Representative($user);
        $representative->setOfficialTitle('Vice President');
        $representative->setCity('Los Angeles');
        $representative->setAddress('6153 Smokey Ln');
        $representative->setPhone('(672)-586-7816');
        $representative->setPrivatePhone($faker->phoneNumber);
        $representative->setEmail('josephb26@example.com');
        $representative->setPrivateEmail($faker->companyEmail);
        $representative->setDistrict($district);
        $representative->setIsNonLegislative(true);
        $this->addReference('representative_jb', $representative);
        $manager->persist($representative);

        $user = $this->getReference('user_2');
        $district = $this->getReference('district_sf');
        $representative = new Representative($user);
        $representative->setOfficialTitle('CEO');
        $representative->setCity('San Francisco');
        $representative->setAddress('4143 Depaul Dr');
        $representative->setPhone('(385)-369-5636');
        $representative->setPrivatePhone($faker->phoneNumber);
        $representative->setEmail('jeanne.torres49@example.com');
        $representative->setPrivateEmail($faker->companyEmail);
        $representative->setDistrict($district);
        $representative->setUpdatedAt(new \DateTime('+1 week'));
        $this->addReference('representative_jt', $representative);
        $manager->persist($representative);

        $user = $this->getReference('user_3');
        $district = $this->getReference('district_sd');
        $representative = new Representative($user);
        $representative->setOfficialTitle('Software Engineer');
        $representative->setCity('San Diego');
        $representative->setAddress('1730 Auerbach Ave');
        $representative->setPhone('(731)-567-8228');
        $representative->setPrivatePhone($faker->phoneNumber);
        $representative->setEmail('willie.carroll20@example.com');
        $representative->setPrivateEmail($faker->companyEmail);
        $representative->setDistrict($district);
        $representative->setIsNonLegislative(true);
        $representative->setUpdatedAt(new \DateTime('+1 week'));
        $this->addReference('representative_wc', $representative);
        $manager->persist($representative);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}

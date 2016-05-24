<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\Representative;

class LoadRepresentativeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $representative = new Representative();
        $representative->setFirstName('Joseph');
        $representative->setLastName('Biden');
        $representative->setUsername('JosephBiden');
        $representative->setOfficialTitle('Vice President');
        $representative->setCity('Los Angeles');
        $representative->setOfficialAddress('6153 Smokey Ln');
        $representative->setOfficialPhone('(672)-586-7816');
        $representative->setEmail('josephb26@example.com');
        $representative->setDistrict($this->getReference('district_la'));
        $representative->setRepresentativeStorage($this->getReference('vice_president'));
        $this->addReference('representative_jb', $representative);
        $manager->persist($representative);

        $representative = new Representative();
        $representative->setFirstName('Jeanne');
        $representative->setLastName('Torres');
        $representative->setUsername('JeanneTorres');
        $representative->setOfficialTitle('CEO');
        $representative->setCity('San Francisco');
        $representative->setOfficialAddress('4143 Depaul Dr');
        $representative->setOfficialPhone('(385)-369-5636');
        $representative->setEmail('jeanne.torres49@example.com');
        $representative->setDistrict($this->getReference('district_sf'));
        $this->addReference('representative_jt', $representative);
        $manager->persist($representative);

        $representative = new Representative();
        $representative->setFirstName('Willie');
        $representative->setLastName('Carroll');
        $representative->setUsername('WillieCarroll');
        $representative->setOfficialTitle('Software Engineer');
        $representative->setCity('San Diego');
        $representative->setOfficialAddress('1730 Auerbach Ave');
        $representative->setOfficialPhone('(731)-567-8228');
        $representative->setEmail('willie.carroll20@example.com');
        $representative->setDistrict($this->getReference('district_sd'));
        $this->addReference('representative_wc', $representative);
        $manager->persist($representative);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadDistrictData::class, LoadRepresentativeStorageData::class];
    }
}

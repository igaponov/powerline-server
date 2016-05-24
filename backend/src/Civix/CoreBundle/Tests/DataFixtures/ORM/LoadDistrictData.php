<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\District;

class LoadDistrictData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $district = new District();
        $district->setLabel('United States')
            ->setId(19)
            ->setDistrictType(District::NATIONAL_EXEC);
        $manager->persist($district);
        $this->addReference('district_us', $district);

        $district = new District();
        $district->setLabel('California')
            ->setId(20)
            ->setDistrictType(District::STATE_EXEC);
        $manager->persist($district);
        $this->addReference('district_ca', $district);
        
        $district = new District();
        $district->setLabel('Los Angeles')
            ->setId(21)
            ->setDistrictType(District::LOCAL_EXEC);
        $manager->persist($district);
        $this->addReference('district_la', $district);
        
        $district = new District();
        $district->setLabel('San Francisco')
            ->setId(22)
            ->setDistrictType(District::LOCAL_EXEC);
        $manager->persist($district);
        $this->addReference('district_sf', $district);
        
        $district = new District();
        $district->setLabel('San Diego')
            ->setId(23)
            ->setDistrictType(District::LOCAL_EXEC);
        $manager->persist($district);
        $this->addReference('district_sd', $district);
        
        $manager->flush();
    }
}

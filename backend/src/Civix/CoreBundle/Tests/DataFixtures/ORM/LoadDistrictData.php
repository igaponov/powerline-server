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
        $district->setId(19);
        $district->setLabel('United States');
        $district->setDistrictType(District::NATIONAL_EXEC);
        $this->addReference('district_us', $district);
        $manager->persist($district);
        $manager->flush();
    }
}

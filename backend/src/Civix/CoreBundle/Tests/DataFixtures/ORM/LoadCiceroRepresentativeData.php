<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\CiceroRepresentative;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadCiceroRepresentativeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $representative = new CiceroRepresentative();
        $representative->setId(44926)
            ->setFirstName('Joseph')
            ->setLastName('Biden')
            ->setOfficialTitle('Vice President')
            ->setCity('Washington')
            ->setAddressLine1('The White House')
            ->setAddressLine2('1600 Pennsylvania Avenue NW')
            ->setAddressLine3('')
            ->setPhone('')
            ->setEmail('josephb26@example.com')
            ->setAvatarSourceFileName('http://www.whitehouse.gov/sites/default/files/imagecache/admin_official_thumb/administration-official/ao_image/vp_portrait_hi-res.jpg')
            ->setDistrict($this->getReference('district_us'));
        $this->addReference('cicero_representative_jb', $representative);
        $manager->persist($representative);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadDistrictData::class];
    }
}

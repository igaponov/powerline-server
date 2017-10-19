<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Representative;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadRepresentativeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $representative = new Representative();
        $representative->setCiceroId(123543)
            ->setFirstName('Barack')
            ->setLastName('Obama')
            ->setOfficialTitle('President')
            ->setCity('Washington')
            ->setCountry('US')
            ->setAddressLine1('The White House')
            ->setAddressLine2('1600 Pennsylvania Avenue NW')
            ->setAddressLine3('')
            ->setPhone('202-456-1111')
            ->setEmail('')
            ->setDistrict($this->getReference('district_us'))
            ->setState($this->getReference('state_dc'))
            ->setWebsite('http://www.whitehouse.gov/administration/president_obama/')
            ->setParty('Democrat')
            ->setBirthday(new \DateTime('1961-08-04'));
        $this->addReference('cicero_representative_bo', $representative);
        $manager->persist($representative);

        $representative = new Representative();
        $representative->setCiceroId(44926)
            ->setFirstName('Joseph')
            ->setLastName('Biden')
            ->setOfficialTitle('Vice President')
            ->setCity('Washington')
            ->setCountry('US')
            ->setAddressLine1('The White House')
            ->setAddressLine2('1600 Pennsylvania Avenue NW')
            ->setAddressLine3('')
            ->setPhone('')
            ->setEmail('josephb26@example.com')
            ->setDistrict($this->getReference('district_us'))
            ->setState($this->getReference('state_dc'))
            ->setWebsite('http://www.whitehouse.gov/administration/vice-president-biden')
            ->setParty('Democrat')
            ->setBirthday(new \DateTime('1942-11-20'))
            ->setOpenstateId('os_id_01');
        $this->addReference('cicero_representative_jb', $representative);
        $manager->persist($representative);

        $representative = new Representative();
        $representative->setCiceroId(103224)
            ->setFirstName('Robert')
            ->setLastName('Menendez')
            ->setOfficialTitle('Senator')
            ->setCountry('US')
            ->setCity('Washington')
            ->setAddressLine1('United States Senate')
            ->setAddressLine2('528 Hart Senate Office Building')
            ->setAddressLine3('')
            ->setPhone('(202) 224-4744')
            ->setEmail('')
            ->setDistrict($this->getReference('district_nj'))
            ->setState($this->getReference('state_dc'))
            ->setWebsite('http://www.senate.gov/')
            ->setParty('Democrat')
            ->setBirthday(new \DateTime('1954-01-01'));
        $this->addReference('cicero_representative_rm', $representative);
        $manager->persist($representative);

        $representative = new Representative();
        $representative->setCiceroId(103108)
            ->setFirstName('Kirsten')
            ->setLastName('Gillibrand')
            ->setOfficialTitle('Senator')
            ->setCountry('US')
            ->setCity('Washington')
            ->setAddressLine1('United States Senate')
            ->setAddressLine2('478 Russell Senate Office Building')
            ->setAddressLine3('')
            ->setPhone('(202) 224-4451')
            ->setEmail('')
            ->setDistrict($this->getReference('district_nj'))
            ->setState($this->getReference('state_dc'))
            ->setWebsite('http://www.senate.gov/')
            ->setParty('Democrat')
            ->setBirthday(new \DateTime('1966-12-09'));
        $this->addReference('cicero_representative_kg', $representative);
        $manager->persist($representative);

        $representative = new Representative();
        $representative->setCiceroId(103199)
            ->setFirstName('Eleanor')
            ->setLastName('Holmes')
            ->setOfficialTitle('Congressman')
            ->setCountry('US')
            ->setCity('Washington')
            ->setAddressLine1('United States Senate')
            ->setAddressLine2('90 K Street')
            ->setAddressLine3('NE Suite 100')
            ->setPhone(' (202) 408-9041')
            ->setEmail('')
            ->setDistrict($this->getReference('district_nj'))
            ->setState($this->getReference('state_dc'))
            ->setWebsite('https://norton.house.gov/')
            ->setParty('Democrat')
            ->setBirthday(new \DateTime('1937-06-13'));
        $this->addReference('cicero_representative_eh', $representative);
        $manager->persist($representative);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadDistrictData::class, LoadStateData::class];
    }
}

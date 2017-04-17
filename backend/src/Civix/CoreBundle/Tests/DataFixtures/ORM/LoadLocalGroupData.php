<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadLocalGroupData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $us = new Group();
        $us
            ->setGroupType(Group::GROUP_TYPE_COUNTRY)
            ->setOfficialName('United States')
            ->setLocationName('US');
        $manager->persist($us);
        $this->addReference('local_group_us', $us);

        $ks = new Group();
        $ks
            ->setGroupType(Group::GROUP_TYPE_STATE)
            ->setOfficialName('Kansas')
            ->setLocationName('KS')
            ->setParent($us);
        $manager->persist($ks);
        $this->addReference('local_group_ks', $ks);

        $bu = new Group();
        $bu
            ->setGroupType(Group::GROUP_TYPE_LOCAL)
            ->setOfficialName('Bucklin')
            ->setLocationName('Bucklin')
            ->setParent($ks);
        $manager->persist($bu);
        $this->addReference('local_group_bu', $bu);

        $eu = new Group();
        $eu
            ->setGroupType(Group::GROUP_TYPE_COUNTRY)
            ->setOfficialName('European Union')
            ->setLocationName('EU');
        $manager->persist($eu);
        $this->addReference('local_group_eu', $eu);

        $es = new Group();
        $es
            ->setGroupType(Group::GROUP_TYPE_COUNTRY)
            ->setOfficialName('Spain')
            ->setLocationName('ES')
            ->setParent($eu);
        $manager->persist($es);
        $this->addReference('local_group_es', $es);

        $cm = new Group();
        $cm
            ->setGroupType(Group::GROUP_TYPE_STATE)
            ->setOfficialName('Comunidad de Madrid')
            ->setLocationName('Comunidad de Madrid')
            ->setParent($es);
        $manager->persist($cm);
        $this->addReference('local_group_cm', $cm);

        $md = new Group();
        $md
            ->setGroupType(Group::GROUP_TYPE_LOCAL)
            ->setOfficialName('Madrid')
            ->setLocationName('Madrid')
            ->setParent($cm);
        $manager->persist($md);
        $this->addReference('local_group_md', $md);

        $au = new Group();
        $au
            ->setGroupType(Group::GROUP_TYPE_COUNTRY)
            ->setOfficialName('African Union')
            ->setLocationName('AFU');
        $manager->persist($au);
        $this->addReference('local_group_au', $au);

        $eg = new Group();
        $eg
            ->setGroupType(Group::GROUP_TYPE_COUNTRY)
            ->setOfficialName('Egypt')
            ->setLocationName('EG')
            ->setParent($au);
        $manager->persist($eg);
        $this->addReference('local_group_eg', $eg);

        $cg = new Group();
        $cg
            ->setGroupType(Group::GROUP_TYPE_STATE)
            ->setOfficialName('Cairo Governorate')
            ->setLocationName('Cairo Governorate')
            ->setParent($eg);
        $manager->persist($cg);
        $this->addReference('local_group_cg', $cg);

        $ca = new Group();
        $ca
            ->setGroupType(Group::GROUP_TYPE_LOCAL)
            ->setOfficialName('Cairo')
            ->setLocationName('CA')
            ->setParent($cg);
        $manager->persist($ca);
        $this->addReference('local_group_ca', $ca);

        $sh = new Group();
        $sh
            ->setGroupType(Group::GROUP_TYPE_LOCAL)
            ->setOfficialName('El Shorouk City')
            ->setLocationName('El Shorouk City')
            ->setParent($cg);
        $manager->persist($sh);
        $this->addReference('local_group_sh', $sh);

        $manager->flush();
    }
}

<?php

namespace Civix\FrontBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadGroupData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $groupUs = new Group();
        $groupUs->setOfficialName('The United States of America')
            ->setGroupType(Group::GROUP_TYPE_COUNTRY)
            ->setManagerEmail('support@powerli.ne')
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_PUBLIC)
            ->setAcronym('US')
            ->setLocationName('US')
            ->setTransparency(Group::GROUP_TRANSPARENCY_PUBLIC)
            ->setSlug('us');
        $manager->persist($groupUs);
        $this->addReference('group_us', $groupUs);

        $groupCa = new Group();
        $groupCa->setOfficialName('California')
            ->setGroupType(Group::GROUP_TYPE_STATE)
            ->setManagerEmail('support@powerli.ne')
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_PUBLIC)
            ->setAcronym('CA')
            ->setLocationName('CA')
            ->setTransparency(Group::GROUP_TRANSPARENCY_PUBLIC)
            ->setSlug('ca')
            ->setParent($groupUs);
        $manager->persist($groupCa);
        $this->addReference('group_ca', $groupCa);

        $group = new Group();
        $group->setOfficialName('Los Angeles')
            ->setGroupType(Group::GROUP_TYPE_LOCAL)
            ->setManagerEmail('support@powerli.ne')
            ->setMembershipControl(Group::GROUP_MEMBERSHIP_PUBLIC)
            ->setAcronym('AK')
            ->setLocationName('Los Angeles')
            ->setTransparency(Group::GROUP_TRANSPARENCY_PUBLIC)
            ->setSlug('la')
            ->setParent($groupCa);
        $manager->persist($group);
        $this->addReference('group_la', $group);

        $manager->flush();
    }

}
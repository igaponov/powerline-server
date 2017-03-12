<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Report;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFieldsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadMembershipReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_4');
        /** @var Group $group */
        $group = $this->getReference('group_1');
        /** @var Group\GroupField $field */
        $field = $this->getReference('another-group-field');
        $report = new MembershipReport(
            $user->getId(),
            $group->getId(),
            [$field->getId() => 'Test Answer']
        );
        $manager->persist($report);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserGroupData::class, LoadGroupFieldsData::class];
    }
}
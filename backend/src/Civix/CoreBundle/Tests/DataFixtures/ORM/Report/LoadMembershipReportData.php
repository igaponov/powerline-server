<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Report;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFieldsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadMembershipReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');
        /** @var Group $group */
        $group = $this->getReference('group_1');
        /** @var Group\GroupField $testField */
        $testField = $this->getReference('test-group-field');
        $report = new MembershipReport(
            $user3->getId(),
            $group->getId(),
            [$testField->getFieldName() => 'Test Answer']
        );
        $manager->persist($report);
        $report = new MembershipReport(
            $user4->getId(),
            $group->getId(),
            [$testField->getFieldName() => 'Second Answer']
        );
        $manager->persist($report);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupFieldsData::class,
        ];
    }
}
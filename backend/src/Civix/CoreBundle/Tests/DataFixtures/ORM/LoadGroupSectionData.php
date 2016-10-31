<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupSection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadGroupSectionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Group $group */
        $group = $this->getReference('group_1');

        $section = new GroupSection();
        $section->setTitle('group_1_section_1');
        $group->addGroupSection($section);
        $this->addReference('group_1_section_1', $section);
        $manager->persist($group);

        $section = new GroupSection();
        $section->setTitle('group_1_section_2');
        $group->addGroupSection($section);
        $this->addReference('group_1_section_2', $section);
        $manager->persist($group);

        /** @var Group $group */
        $group = $this->getReference('group_3');

        $section = new GroupSection();
        $section->setTitle('group_3_section_1');
        $group->addGroupSection($section);
        $this->addReference('group_3_section_1', $section);
        $manager->persist($group);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
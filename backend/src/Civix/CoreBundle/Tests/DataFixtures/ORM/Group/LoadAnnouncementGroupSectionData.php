<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadAnnouncementGroupSectionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Announcement $announcement1 */
        $announcement1 = $this->getReference('announcement_group_1');
        /** @var Announcement $announcement3 */
        $announcement3 = $this->getReference('announcement_group_3');

        $section1 = $this->getReference('group_1_section_1');
        $section3 = $this->getReference('group_3_section_1');

        $announcement1->addGroupSection($section1);
        $manager->persist($announcement1);
        $announcement3->addGroupSection($section3);
        $manager->persist($announcement3);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupAnnouncementData::class, LoadGroupSectionData::class];
    }
}
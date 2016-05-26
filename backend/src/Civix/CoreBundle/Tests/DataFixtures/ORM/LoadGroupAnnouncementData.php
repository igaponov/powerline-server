<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Announcement\GroupAnnouncement;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadGroupAnnouncementData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        
        $group = $this->getReference('group');

        $announcement = new GroupAnnouncement();
        $announcement->setUser($group);
        $announcement->setContent($faker->text);
        $this->addReference('announcement_group_1', $announcement);
        $manager->persist($announcement);

        //published
        $announcementPublished = new GroupAnnouncement();
        $announcementPublished->setUser($group);
        $announcementPublished->setContent($faker->text);
        $announcementPublished->setPublishedAt(new \DateTime('-1 month'));
        $this->addReference('announcement_group_2', $announcementPublished);
        $manager->persist($announcementPublished);

        $announcementPublished = new GroupAnnouncement();
        $announcementPublished->setUser($group);
        $announcementPublished->setContent($faker->text);
        $announcementPublished->setPublishedAt(new \DateTime());
        $this->addReference('announcement_group_3', $announcementPublished);
        $manager->persist($announcementPublished);

        $group = $this->getReference('testfollowsecretgroups');

        $announcement = new GroupAnnouncement();
        $announcement->setUser($group);
        $announcement->setContent($faker->text);
        $this->addReference('announcement_secretgroup_1', $announcement);
        $manager->persist($announcement);

        $group = $this->getReference('testfollowprivategroups');

        $announcement = new GroupAnnouncement();
        $announcement->setUser($group);
        $announcement->setContent($faker->text);
        $announcement->setPublishedAt(new \DateTime());
        $this->addReference('announcement_privategroup_1', $announcement);
        $manager->persist($announcement);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}

<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Announcement\GroupAnnouncement;
use Civix\CoreBundle\Entity\Group;
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
        /** @var Group $group */
        $group = $this->getReference('group_1');

        $announcement = new GroupAnnouncement();
        $announcement->setRoot($group);
        $announcement->setContent($faker->sentence);
        $this->addReference('announcement_group_1', $announcement);
        $manager->persist($announcement);

        //published
        $announcementPublished = new GroupAnnouncement();
        $announcementPublished->setRoot($group);
        $announcementPublished->setContent($faker->sentence);
        $announcementPublished->setPublishedAt(new \DateTime('-1 month'));
        $this->addReference('announcement_group_2', $announcementPublished);
        $manager->persist($announcementPublished);

        $announcementPublished = new GroupAnnouncement();
        $announcementPublished->setRoot($group);
        $announcementPublished->setContent($faker->sentence);
        $announcementPublished->setPublishedAt(new \DateTime());
        $this->addReference('announcement_group_3', $announcementPublished);
        $manager->persist($announcementPublished);

        $group = $this->getReference('group_2');

        $announcement = new GroupAnnouncement();
        $announcement->setRoot($group);
        $announcement->setContent($faker->sentence);
        $announcement->setPublishedAt(new \DateTime());
        $this->addReference('announcement_private_1', $announcement);
        $manager->persist($announcement);

        $group = $this->getReference('group_3');

        $announcement = new GroupAnnouncement();
        $announcement->setRoot($group);
        $announcement->setContent($faker->sentence);
        $this->addReference('announcement_secret_1', $announcement);
        $manager->persist($announcement);

        $announcementPublished = new GroupAnnouncement();
        $announcementPublished->setRoot($group);
        $announcementPublished->setContent($faker->sentence);
        $announcementPublished->setPublishedAt(new \DateTime());
        $this->addReference('announcement_secret_2', $announcementPublished);
        $manager->persist($announcementPublished);

        $group = $this->getReference('group_4');

        $announcement = new GroupAnnouncement();
        $announcement->setRoot($group);
        $announcement->setContent($faker->sentence);
        $this->addReference('announcement_topsecret_1', $announcement);
        $manager->persist($announcement);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}

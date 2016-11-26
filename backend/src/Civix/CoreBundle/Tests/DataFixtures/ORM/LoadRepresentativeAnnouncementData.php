<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement;
use Faker\Factory;

class LoadRepresentativeAnnouncementData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        
        $representative = $this->getReference('representative_jb');

        $announcement = new RepresentativeAnnouncement();
        $announcement->setRoot($representative);
        $announcement->setContent($faker->sentence);
        $this->addReference('announcement_jb_1', $announcement);
        $manager->persist($announcement);

        //published
        $announcementPublished = new RepresentativeAnnouncement();
        $announcementPublished->setRoot($representative);
        $announcementPublished->setContent($faker->sentence);
        $announcementPublished->setPublishedAt(new \DateTime('-1 month'));
        $this->addReference('announcement_jb_2', $announcementPublished);
        $manager->persist($announcementPublished);

        $announcementPublished = new RepresentativeAnnouncement();
        $announcementPublished->setRoot($representative);
        $announcementPublished->setContent($faker->sentence);
        $announcementPublished->setPublishedAt(new \DateTime());
        $this->addReference('announcement_jb_3', $announcementPublished);
        $manager->persist($announcementPublished);

        $representative = $this->getReference('representative_jt');

        $announcement = new RepresentativeAnnouncement();
        $announcement->setRoot($representative);
        $announcement->setContent($faker->sentence);
        $this->addReference('announcement_jt_1', $announcement);
        $manager->persist($announcement);

        $representative = $this->getReference('representative_wc');

        $announcement = new RepresentativeAnnouncement();
        $announcement->setRoot($representative);
        $announcement->setContent($faker->sentence);
        $announcement->setPublishedAt(new \DateTime());
        $this->addReference('announcement_wc_1', $announcement);
        $manager->persist($announcement);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadRepresentativeData::class];
    }
}

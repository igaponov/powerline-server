<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadGroupAnnouncementReadData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Announcement $announcement */
        $announcement = $this->getReference('announcement_group_3');
        /** @var User $user */
        $user = $this->getReference('user_1');
        $announcement->markAsRead($user);
        $manager->persist($announcement);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupAnnouncementData::class];
    }
}

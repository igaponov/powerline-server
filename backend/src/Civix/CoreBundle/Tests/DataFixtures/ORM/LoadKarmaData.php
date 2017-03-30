<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadKarmaData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_1');

        $karma = new Karma($user, Karma::TYPE_VIEW_ANNOUNCEMENT, 25);
        $manager->persist($karma);

        $karma = new Karma($user, Karma::TYPE_FOLLOW, 10, ['follower_id' => 1]);
        $manager->persist($karma);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}

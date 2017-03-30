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
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');

        $karma = new Karma($user1, Karma::TYPE_VIEW_ANNOUNCEMENT, 25);
        $manager->persist($karma);

        $karma = new Karma($user1, Karma::TYPE_FOLLOW, 10, ['following_id' => 3]);
        $manager->persist($karma);

        $karma = new Karma($user3, Karma::TYPE_APPROVE_FOLLOW_REQUEST, 10, ['follower_id' => 1]);
        $manager->persist($karma);

        $karma = new Karma($user1, Karma::TYPE_JOIN_GROUP, 10, ['group_id' => 1]);
        $manager->persist($karma);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}

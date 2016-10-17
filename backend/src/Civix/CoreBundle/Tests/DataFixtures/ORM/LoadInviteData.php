<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadInviteData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_1');
        $group2 = $this->getReference('group_2');
        $group3 = $this->getReference('group_3');

        $user->addInvite($group2);
        $user->addInvite($group3);

        $manager->persist($user);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}

<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserLocalGroupData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var Group $us */
        $us = $this->getReference('local_group_us');
        /** @var Group $ks */
        $ks = $this->getReference('local_group_ks');
        /** @var Group $bu */
        $bu = $this->getReference('local_group_bu');
        /** @var Group $eu */
        $eu = $this->getReference('local_group_eu');
        /** @var Group $es */
        $es = $this->getReference('local_group_es');
        /** @var Group $cm */
        $cm = $this->getReference('local_group_cm');
        /** @var Group $md */
        $md = $this->getReference('local_group_md');
        /** @var Group $au */
        $au = $this->getReference('local_group_au');
        /** @var Group $eg */
        $eg = $this->getReference('local_group_eg');
        /** @var Group $cg */
        $cg = $this->getReference('local_group_cg');
        /** @var Group $ca */
        $ca = $this->getReference('local_group_ca');

        $userGroup = new UserGroup($user1, $us);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user1, $ks);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user1, $bu);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user2, $eu);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user2, $es);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user2, $cm);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user2, $md);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user3, $au);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user3, $eg);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user3, $cg);
        $manager->persist($userGroup);
        $userGroup = new UserGroup($user3, $ca);
        $manager->persist($userGroup);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadLocalGroupData::class];
    }
}

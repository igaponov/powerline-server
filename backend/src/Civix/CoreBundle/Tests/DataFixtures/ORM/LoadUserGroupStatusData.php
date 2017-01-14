<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\User;

class LoadUserGroupStatusData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');

        /** @var Group $group1 */
        $group1 = $this->getReference('group_1');

        $userGroup = new UserGroup($user2, $group1);
        $userGroup->setStatus(UserGroup::STATUS_PENDING);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user3, $group1);
        $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user4, $group1);
        $userGroup->setStatus(UserGroup::STATUS_BANNED);
        $manager->persist($userGroup);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}

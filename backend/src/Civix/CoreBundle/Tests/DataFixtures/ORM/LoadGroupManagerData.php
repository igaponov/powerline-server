<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroupManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\User;

class LoadGroupManagerData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');

        /** @var Group $group1 */
        $group1 = $this->getReference('group_1');
        /** @var Group $group2 */
        $group2 = $this->getReference('group_2');
        /** @var Group $group3 */
        $group3 = $this->getReference('group_3');

        $groupManager = new UserGroupManager($user2, $group1);
        $manager->persist($groupManager);

        $groupManager = new UserGroupManager($user3, $group1);
        $manager->persist($groupManager);

        $groupManager = new UserGroupManager($user2, $group3);
        $manager->persist($groupManager);

        $groupManager = new UserGroupManager($user3, $group2);
        $manager->persist($groupManager);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}

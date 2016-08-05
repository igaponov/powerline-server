<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\User;

class LoadUserGroupData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');

        /** @var Group $group1 */
        $group1 = $this->getReference('group_1');
        /** @var Group $group2 */
        $group2 = $this->getReference('group_2');
        /** @var Group $group3 */
        $group3 = $this->getReference('group_3');
        /** @var Group $group4 */
        $group4 = $this->getReference('group_4');

        $userGroup = new UserGroup($user4, $group1);
        $userGroup->setPermissionsName(true)
            ->setPermissionsCity(true)
            ->setPermissionsPhone(true)
            ->setPermissionsApprovedAt(new \DateTime());
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user4, $group3);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user3, $group4);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user1, $group2);
        $manager->persist($userGroup);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}

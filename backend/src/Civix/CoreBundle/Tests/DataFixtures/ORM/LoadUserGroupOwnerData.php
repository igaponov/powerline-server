<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserGroupOwnerData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user3 = $this->getReference('user_3');
        $group1 = $this->getReference('group_1');
        $group2 = $this->getReference('group_2');
        $group3 = $this->getReference('group_3');
        $group4 = $this->getReference('group_4');


        $userGroup = new UserGroup($user1, $group1);
        $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user2, $group2);
        $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user3, $group3);
        $userGroup->setStatus(UserGroup::STATUS_ACTIVE)
            ->setCreatedAt(new \DateTime('-1 day'));
        $manager->persist($userGroup);

        $userGroup = new UserGroup($user1, $group4);
        $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        $manager->persist($userGroup);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
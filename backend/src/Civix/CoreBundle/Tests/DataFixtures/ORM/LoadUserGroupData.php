<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\User;

/**
 * LoadUserGroupData.
 *
 * @author Habibillah <habibillah@gmail.com>
 */
class LoadUserGroupData extends AbstractFixture implements DependentFixtureInterface
{

    /** @var ObjectManager $manager */
    private $manager;

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $users = array(
            $this->getReference('userfollowtest1'),
            $this->getReference('userfollowtest2'),
            $this->getReference('userfollowtest3'),
        );

        /** @noinspection PhpParamsInspection */
        $this->createUserGroup($this->getReference('testfollowsecretgroups'), $users);

        /** @noinspection PhpParamsInspection */
        $this->createUserGroup($this->getReference('testfollowprivategroups'), $users);
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadGroupData::class];
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param Group $group
     * @param User[] $members
     */
    private function createUserGroup($group, $members)
    {
        foreach ($members as $member) {
            $userGroup = new UserGroup($member, $group);
            $this->manager->persist($userGroup);
            $this->setReference($member->getUsername().'_'.$group->getUsername(), $userGroup);
        }

        $this->manager->flush();
    }
}

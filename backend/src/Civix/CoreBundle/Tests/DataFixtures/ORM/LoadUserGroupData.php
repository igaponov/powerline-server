<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\User;

/**
 * LoadUserGroupData.
 */
class LoadUserGroupData extends AbstractFixture implements OrderedFixtureInterface
{

    /** @var ObjectManager $manager */
    private $manager;

    /**
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

    public function getOrder()
    {
        return 13;
    }

    /**
     * @param Group $group
     * @param User[] $members
     */
    private function createUserGroup($group, $members)
    {
        foreach ($members as $member) {
            $userGroup = new UserGroup($member, $group);
            $this->manager->persist($userGroup);
            $this->setReference($member->getUsername(), $member);
        }

        $this->manager->flush();
    }
}

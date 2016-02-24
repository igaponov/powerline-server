<?php

namespace Civix\CoreBundle\Tests\Entity\Micropetitions;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;

class PetitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group petition
     */
    public function testGetQuorumCount()
    {
        $petition = new Petition();
        $group = new Group();
        $petition->setGroup($group);

        $this->assertEquals(0, $petition->getQuorumCount());

        /* 10 users in a group */
        for ($i = 0; $i < 10; ++$i) {
            $userGroup = new UserGroup(new User(), $group);
            $group->addUser($userGroup);
        }

        $this->assertEquals(1, $petition->getQuorumCount());

        $group->setPetitionPercent(30);
        $this->assertEquals(3, $petition->getQuorumCount());

        /* 112 users in a group (33 to quorum for 30%)*/
        for ($i = 0; $i < 102; ++$i) {
            $userGroup = new UserGroup(new User(), $group);
            $group->addUser($userGroup);
        }
        $this->assertGreaterThan(33, $petition->getQuorumCount());
    }
}

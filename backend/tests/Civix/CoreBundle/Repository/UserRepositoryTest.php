<?php

namespace Tests\Civix\CoreBundle\Repository;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Tests\Civix\CoreBundle\DataFixtures\ORM\Issue\PM590;

class UserRepositoryTest extends WebTestCase
{
    public function testGetUsersByFollowingForPush(): void
    {
        $repository = $this->loadFixtures([
            PM590::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $follower = $repository->getReference('user_2');
        $service = $this->getContainer()->get('civix_core.repository.user_repository');
        $followers = $service->getUsersByFollowingForPush($user);
        $this->assertCount(1, $followers);
        $this->assertSame($follower->getId(), $followers[0]->getId());
    }

    public function testFollowedDoNotDisturbTill(): void
    {
        $repository = $this->loadFixtures([
            PM590::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $service = $this->getContainer()->get('civix_core.repository.user_repository');
        $followers = $service->getUsersByFollowingForPush($user);
        $this->assertCount(0, $followers);
    }

    public function testFindAllMembersByGroup()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('user_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $service = $this->getContainer()->get('civix_core.repository.user_repository');
        $users = $service->findAllMembersByGroup($group);
        $this->assertCount(4, $users);
        $this->assertSame($user2, $users[0]);
        $this->assertSame($user3, $users[1]);
        $this->assertSame($user4, $users[2]);
        $this->assertSame($user1, $users[3]);
    }

    public function testFindAllMembersByGroupWithExcluded()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('user_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $service = $this->getContainer()->get('civix_core.repository.user_repository');
        $users = $service->findAllMembersByGroup($group, $user3);
        $this->assertCount(2, $users);
        $this->assertSame($user1, $users[0]);
        $this->assertSame($user2, $users[1]);
    }
}

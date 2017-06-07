<?php

namespace Tests\Civix\CoreBundle\Repository;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
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
}

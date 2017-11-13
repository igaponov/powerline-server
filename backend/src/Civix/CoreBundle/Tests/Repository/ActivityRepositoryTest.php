<?php
namespace Civix\CoreBundle\Tests\Repository;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\QueryFunction\CountPriorityActivities;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityRelationsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSubscriberData;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class ActivityRepositoryTest extends WebTestCase
{
    public function testCountPriorityActivitiesByUser(): void
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadActivityRelationsData::class,
            LoadUserPetitionSubscriberData::class,
            LoadPostSubscriberData::class,
            LoadPollSubscriberData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $query = new CountPriorityActivities($em);
        $count = $query($user, new \DateTime('-30 days'));
        $this->assertSame(5, $count);
    }
}
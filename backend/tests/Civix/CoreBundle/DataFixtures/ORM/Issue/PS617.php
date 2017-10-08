<?php

namespace Tests\Civix\CoreBundle\DataFixtures\ORM\Issue;

use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Service\SocialActivityFactory;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class PS617 extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $factory = new SocialActivityFactory();

        /** @var UserFollow $userFollow2 */
        $userFollow2 = $this->getReference('user_2_user_1');
        /** @var UserFollow $userFollow3 */
        $userFollow3 = $this->getReference('user_3_user_1');
        /** @var UserFollow $userFollow4 */
        $userFollow4 = $this->getReference('user_4_user_1');

        $activity = $factory->createFollowRequestActivity($userFollow2);
        $manager->persist($activity);
        $activity = $factory->createFollowRequestActivity($userFollow3);
        $manager->persist($activity);
        $activity = $factory->createFollowRequestActivity($userFollow4);
        $manager->persist($activity);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadUserFollowerData::class];
    }
}
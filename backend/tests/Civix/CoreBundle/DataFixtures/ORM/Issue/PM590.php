<?php

namespace Tests\Civix\CoreBundle\DataFixtures\ORM\Issue;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class PM590 extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        $user2->setDoNotDisturb(false)
            ->setFollowedDoNotDisturbTill(new \DateTime('-1 second'));
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        $user3->setDoNotDisturb(false)
            ->setFollowedDoNotDisturbTill(new \DateTime('+3 days'));
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');

        $userFollow = new UserFollow();
        $userFollow->setUser($user1)
            ->setFollower($user2)
            ->setStatus(UserFollow::STATUS_ACTIVE);
        $manager->persist($userFollow);

        $userFollow = new UserFollow();
        $userFollow->setUser($user1)
            ->setFollower($user3)
            ->setStatus(UserFollow::STATUS_PENDING);
        $manager->persist($userFollow);

        $userFollow = new UserFollow();
        $userFollow->setUser($user1)
            ->setFollower($user4)
            ->setStatus(UserFollow::STATUS_ACTIVE)
            ->setNotifying(false);
        $manager->persist($userFollow);

        $userFollow = new UserFollow();
        $userFollow->setUser($user2)
            ->setFollower($user3)
            ->setStatus(UserFollow::STATUS_ACTIVE);
        $manager->persist($userFollow);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadUserData::class];
    }
}
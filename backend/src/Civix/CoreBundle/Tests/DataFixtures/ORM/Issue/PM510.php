<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Issue;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class PM510 extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');

        $userFollow = new UserFollow();
        $userFollow->setUser($user1)
            ->setFollower($user2)
            ->setDateCreate(new \DateTime('-25 months'))
            ->setStatus(UserFollow::STATUS_PENDING);
        $manager->persist($userFollow);

        $userFollow = new UserFollow();
        $userFollow->setUser($user1)
            ->setFollower($user3)
            ->setDateCreate(new \DateTime('-2 months'))
            ->setStatus(UserFollow::STATUS_ACTIVE);
        $manager->persist($userFollow);

        $userFollow = new UserFollow();
        $userFollow->setUser($user1)
            ->setFollower($user4)
            ->setDateCreate(new \DateTime('-23 months'))
            ->setStatus(UserFollow::STATUS_PENDING);
        $manager->persist($userFollow);
        
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
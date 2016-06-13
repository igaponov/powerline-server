<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserFollowData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var  ObjectManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $follower = $this->getReference('followertest');
        $user1 = $this->getReference('userfollowtest1');
        $user2 = $this->getReference('userfollowtest2');
        $user3 = $this->getReference('userfollowtest3');
        
        $userFollow = $this->generateUserFollow($user1, $follower, UserFollow::STATUS_ACTIVE);
        $this->manager->persist($userFollow);
        $userFollow = $this->generateUserFollow($user2, $follower, UserFollow::STATUS_PENDING);
        $this->manager->persist($userFollow);
        $this->addReference('userfollowtest2_followertest', $userFollow);
        $userFollow = $this->generateUserFollow($user3, $follower, UserFollow::STATUS_ACTIVE);
        $this->manager->persist($userFollow);
        
        $this->manager->flush();
    }

    /**
     * @param User $user
     * @param User$follower
     * @param $status
     * @return UserFollow
     */
    private function generateUserFollow($user, $follower, $status)
    {
        $userFollow = new UserFollow();
        $userFollow->setUser($user);
        $userFollow->setFollower($follower);
        $userFollow->setDateCreate(new \DateTime());
        if ($status == UserFollow::STATUS_ACTIVE) {
            $userFollow->setDateApproval(new \DateTime('-1 week'));
        }
        $userFollow->setStatus($status);
        $user->addFollowing($userFollow);
        $follower->addFollower($userFollow);
        
        return $userFollow;
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
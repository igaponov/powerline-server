<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Activities\CrowdfundingPaymentRequest;
use Civix\CoreBundle\Entity\Activities\LeaderEvent;
use Civix\CoreBundle\Entity\Activities\LeaderNews;
use Civix\CoreBundle\Entity\Activities\MicroPetition;
use Civix\CoreBundle\Entity\Activities\PaymentRequest;
use Civix\CoreBundle\Entity\Activities\Petition;
use Civix\CoreBundle\Entity\Activities\Question;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityCondition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * LoadUserData.
 */
class LoadActivityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $user = $this->getReference('followertest');
        $manager->persist($this->generateActivity(new LeaderNews(), $user));
        $manager->persist($this->generateActivity(new CrowdfundingPaymentRequest(), $user, new \DateTime('-1 hour')));
        $manager->persist($this->generateActivity(new MicroPetition(), $user));
        $user2 = $this->getReference('userfollowtest1');
        $manager->persist($this->generateActivity(new Petition(), $user2));
        $manager->persist($this->generateActivity(new Question(), $user2, new \DateTime('-1 day')));
        $user3 = $this->getReference('userfollowtest2');
        $manager->persist($this->generateActivity(new LeaderEvent(), $user3, new \DateTime('-1 month')));
        $manager->persist($this->generateActivity(new PaymentRequest(), $user3));
        $activity = $this->generateActivity(new Petition(), $user3);
        $activity->setSentAt(new \DateTime('-2 months'));
        $manager->persist($activity);
        $manager->flush();
    }

    private function generateActivity(Activity $activity, $user, $expired = null)
    {
        $faker = Factory::create();
        $activity->setTitle($faker->word);
        $activity->setDescription($faker->text);
        $activity->setSentAt($faker->dateTimeBetween('-10 days', '-1 minute'));
        $activity->setExpireAt($expired);
        $activity->setOwner([]);
        $activity->setUser($user);
        $activityCondition = new ActivityCondition();
        $activityCondition->setUserId($user->getId());
        $activityCondition->addUsers($user);
        $activity->addActivityCondition($activityCondition);

        return $activity;
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}

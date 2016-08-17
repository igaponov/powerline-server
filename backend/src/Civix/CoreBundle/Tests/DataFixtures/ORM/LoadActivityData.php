<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Activities\CrowdfundingPaymentRequest;
use Civix\CoreBundle\Entity\Activities\LeaderEvent;
use Civix\CoreBundle\Entity\Activities\LeaderNews;
use Civix\CoreBundle\Entity\Activities\UserPetition;
use Civix\CoreBundle\Entity\Activities\PaymentRequest;
use Civix\CoreBundle\Entity\Activities\Petition;
use Civix\CoreBundle\Entity\Activities\Question;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityCondition;
use Civix\CoreBundle\Entity\Group;
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
        $leaderNews = $this->generateActivity(new LeaderNews(), $user);
        $manager->persist($leaderNews);
        $this->addReference('activity_leader_news', $leaderNews);
        $crowdfundingPaymentRequest = $this->generateActivity(
            new CrowdfundingPaymentRequest(),
            $user,
            new \DateTime('-1 hour')
        );
        $manager->persist($crowdfundingPaymentRequest);
        $this->addReference('activity_crowdfunding_payment_request', $crowdfundingPaymentRequest);
        $micropetition = $this->generateActivity(new UserPetition(), $user);
        $manager->persist($micropetition);
        $this->addReference('activity_micropetition', $micropetition);
        $group = $this->getReference('testfollowprivategroups');
        $user2 = $this->getReference('userfollowtest1');
        $petition = $this->generateActivity(new Petition(), $user2, null, $group);
        $manager->persist($petition);
        $this->addReference('activity_petition', $petition);
        $question = $this->generateActivity(new Question(), $user2, new \DateTime('-1 day'));
        $manager->persist($question);
        $this->addReference('activity_question', $question);
        $user3 = $this->getReference('userfollowtest2');
        $leaderEvent = $this->generateActivity(new LeaderEvent(), $user3, new \DateTime('-1 month'));
        $manager->persist($leaderEvent);
        $this->addReference('activity_leader_event', $leaderEvent);
        $paymentRequest = $this->generateActivity(new PaymentRequest(), $user3);
        $manager->persist($paymentRequest);
        $this->addReference('activity_payment_request', $paymentRequest);
        $petition = $this->generateActivity(new Petition(), $user3);
        $petition->setSentAt(new \DateTime('-2 months'));
        $manager->persist($petition);
        $this->addReference('activity_petition2', $petition);
        $manager->flush();
    }

    private function generateActivity(Activity $activity, $user, $expired = null, Group $group = null)
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
        if ($group) {
            $activityCondition->setGroupId($group->getId());
        }
        $activity->addActivityCondition($activityCondition);

        return $activity;
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadGroupFollowerTestData::class];
    }
}

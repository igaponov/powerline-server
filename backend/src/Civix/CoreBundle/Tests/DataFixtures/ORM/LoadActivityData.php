<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Activities\CrowdfundingPaymentRequest;
use Civix\CoreBundle\Entity\Activities\LeaderEvent;
use Civix\CoreBundle\Entity\Activities\LeaderNews;
use Civix\CoreBundle\Entity\Activities\Post;
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

/**
 * LoadUserData.
 */
class LoadActivityData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');
        $leaderNews = $this->generateActivity(new LeaderNews(), $user1);
        $manager->persist($leaderNews);
        $this->addReference('activity_leader_news', $leaderNews);
        $crowdfundingPaymentRequest = $this->generateActivity(
            new CrowdfundingPaymentRequest(),
            $user1,
            new \DateTime('-1 hour')
        );
        $manager->persist($crowdfundingPaymentRequest);
        $this->addReference('activity_crowdfunding_payment_request', $crowdfundingPaymentRequest);
        $userPetition = $this->generateActivity(new UserPetition(), $user1);
        $manager->persist($userPetition);
        $this->addReference('activity_user_petition', $userPetition);
        $post = $this->generateActivity(new Post(), $user1);
        $manager->persist($post);
        $this->addReference('activity_post', $post);
        $group = $this->getReference('group_1');
        $user2 = $this->getReference('user_2');
        $petition = $this->generateActivity(new Petition(), $user2, null, $group);
        $manager->persist($petition);
        $this->addReference('activity_petition', $petition);
        $question = $this->generateActivity(new Question(), $user2, new \DateTime('-1 day'));
        $manager->persist($question);
        $this->addReference('activity_question', $question);
        $user3 = $this->getReference('user_3');
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
        return [LoadUserData::class, LoadGroupData::class];
    }
}

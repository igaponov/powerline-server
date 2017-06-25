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
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\User;
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
        /** @var Group $group */
        $group = $this->getReference('group_1');
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        $leaderNews = $this->generateActivity(new LeaderNews(), $user1, $group);
        $manager->persist($leaderNews);
        $this->addReference('activity_leader_news', $leaderNews);
        $crowdfundingPaymentRequest = $this->generateActivity(
            new CrowdfundingPaymentRequest(),
            $user1,
            $group,
            new \DateTime('-1 hour')
        );
        $manager->persist($crowdfundingPaymentRequest);
        $this->addReference('activity_crowdfunding_payment_request', $crowdfundingPaymentRequest);
        $userPetition = $this->generateActivity(new UserPetition(), $user1, $group);
        $userPetition->setUser($user1);
        $manager->persist($userPetition);
        $this->addReference('activity_user_petition', $userPetition);
        $post = $this->generateActivity(new Post(), $user1, $group);
        $manager->persist($post);
        $this->addReference('activity_post', $post);
        $user2 = $this->getReference('user_2');
        $petition = $this->generateActivity(new Petition(), $user2, $group);
        $manager->persist($petition);
        $this->addReference('activity_petition', $petition);
        $question = $this->generateActivity(new Question(), $user2, $group, new \DateTime('-1 day'));
        $manager->persist($question);
        $this->addReference('activity_question', $question);
        $user3 = $this->getReference('user_3');
        $leaderEvent = $this->generateActivity(new LeaderEvent(), $user3, $group, new \DateTime('-1 month'));
        $manager->persist($leaderEvent);
        $this->addReference('activity_leader_event', $leaderEvent);
        $paymentRequest = $this->generateActivity(new PaymentRequest(), $user3, $group);
        /** @var Representative $representative */
        $representative = $this->getReference('representative_jb');
        $paymentRequest->setRepresentative($representative);
        $manager->persist($paymentRequest);
        $this->addReference('activity_payment_request', $paymentRequest);
        $petition = $this->generateActivity(new Petition(), $user3, $group);
        $petition->setSentAt(new \DateTime('-2 months'));
        $manager->persist($petition);
        $this->addReference('activity_petition2', $petition);
        $manager->flush();
    }

    private function generateActivity(Activity $activity, $user, Group $group, $expired = null)
    {
        $faker = Factory::create();
        $activity->setTitle($faker->word);
        $activity->setDescription($faker->text);
        $activity->setSentAt($faker->dateTimeBetween('-10 days', '-1 minute'));
        $activity->setExpireAt($expired);
        $activity->setOwner([]);
        $activity->setUser($user);
        $activityCondition = new ActivityCondition();
        $activityCondition->setUser($user);
        $activityCondition->addUsers($user);
        if ($group) {
            $activity->setGroup($group);
            $activityCondition->setGroup($group);
        }
        $activity->addActivityCondition($activityCondition);

        return $activity;
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadGroupData::class, LoadRepresentativeData::class];
    }
}

<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Issue;

use Civix\CoreBundle\Entity\Activities\LeaderNews;
use Civix\CoreBundle\Entity\ActivityCondition;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupNewsCommentData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * https://github.com/PowerlineApp/powerline-mobile/issues/354
 */
class PM354 extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $user = $this->getReference('user_1');
        $news = $this->getReference('group_news_1');

        $activity = new LeaderNews();
        $activity->setTitle($faker->word);
        $activity->setDescription($faker->text);
        $activity->setSentAt($faker->dateTimeBetween('-10 days', '-1 minute'));
        $activity->setExpireAt(new \DateTime('+1 month'));
        $activity->setOwner([]);
        $activity->setUser($user);
        $activity->setQuestion($news);
        $activityCondition = new ActivityCondition();
        $activityCondition->setUser($user);
        $activityCondition->addUsers($user);
        $activity->addActivityCondition($activityCondition);
        $this->addReference('activity_pm354', $activity);

        $manager->persist($activity);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupNewsCommentData::class];
    }
}
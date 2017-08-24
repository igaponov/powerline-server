<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Issue;

use Civix\CoreBundle\Entity\Activities\Petition;
use Civix\CoreBundle\Entity\ActivityCondition;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadLocalGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadLocalGroupActivityData extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return [LoadLocalGroupData::class];
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var Group $group */
        $group = $this->getReference('local_group_au');
        $activityCondition = (new ActivityCondition())
            ->setUser($user3)
            ->setGroup($group);
        $activity = (new Petition())
            ->setTitle($faker->word)
            ->setDescription($faker->text)
            ->setSentAt(new \DateTime('-1 week'))
            ->setOwner([])
            ->setGroup($group)
            ->setUser($user3)
            ->addActivityCondition($activityCondition);

        $manager->persist($activity);
        $this->addReference('activity_petition3', $activity);

        $manager->flush();
    }
}
<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadActivityReadAuthorData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user = $this->getReference('user_1');
        /** @var Activity $userPetitionActivity */
        $userPetitionActivity = $this->getReference('activity_user_petition');
        $activityRead = new ActivityRead();
        $activityRead->setUser($user);
        $activityRead->setActivity($userPetitionActivity);
        $manager->persist($activityRead);

        /** @var Activity $postActivity */
        $postActivity = $this->getReference('activity_post');
        $activityRead = new ActivityRead();
        $activityRead->setUser($user);
        $activityRead->setActivity($postActivity);
        $manager->persist($activityRead);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadActivityData::class];
    }
}
<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadUserPetitionSubscriberData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var UserPetition $userPetition */
        $userPetition = $this->getReference('user_petition_1');
        /** @var User $user */
        $user = $this->getReference('user_1');
        $user->addPetitionSubscription($userPetition);
        $manager->persist($userPetition);

        /** @var UserPetition $userPetition */
        $userPetition = $this->getReference('user_petition_5');

        /** @var User $user */
        $user = $this->getReference('user_2');
        $user->addPetitionSubscription($userPetition);

        /** @var User $user */
        $user = $this->getReference('user_3');
        $user->addPetitionSubscription($userPetition);

        $manager->persist($userPetition);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserPetitionData::class];
    }
}
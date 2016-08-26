<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserData.
 */
class LoadPollSubscriberData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Question $poll */
        $poll = $this->getReference('group_question_3');
        /** @var User $user */
        $user = $this->getReference('user_1');
        $user->addPollSubscription($poll);
        /** @var User $user */
        $user = $this->getReference('user_4');
        $user->addPollSubscription($poll);

        $manager->persist($poll);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupQuestionData::class];
    }
}
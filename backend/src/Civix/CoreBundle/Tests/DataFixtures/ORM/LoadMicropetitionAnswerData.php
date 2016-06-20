<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Answer;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserGroupData.
 *
 * @author Habibillah <habibillah@gmail.com>
 */
class LoadMicropetitionAnswerData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getReference('followertest');
        $user1 = $this->getReference('userfollowtest1');
        $user2 = $this->getReference('userfollowtest2');
        $user3 = $this->getReference('userfollowtest3');
        $petition1 = $this->getReference('micropetition_1');
        $petition5 = $this->getReference('micropetition_5');

        $answer = new Answer();
        $answer->setUser($user);
        $answer->setPetition($petition1);
        $answer->setOptionId(Petition::OPTION_ID_UPVOTE);
        $answer->setCreatedAt(new \DateTime('-2 months'));
        $manager->persist($answer);

        $answer = new Answer();
        $answer->setUser($user2);
        $answer->setPetition($petition1);
        $answer->setOptionId(Petition::OPTION_ID_DOWNVOTE);
        $answer->setCreatedAt(new \DateTime('-3 months'));
        $manager->persist($answer);

        $answer = new Answer();
        $answer->setUser($user3);
        $answer->setPetition($petition1);
        $answer->setOptionId(Petition::OPTION_ID_IGNORE);
        $answer->setCreatedAt(new \DateTime('-1 month'));
        $manager->persist($answer);

        $answer = new Answer();
        $answer->setUser($user1);
        $answer->setPetition($petition5);
        $answer->setOptionId(Petition::OPTION_ID_UPVOTE);
        $answer->setCreatedAt(new \DateTime('-1 month'));
        $manager->persist($answer);

        $answer = new Answer();
        $answer->setUser($user2);
        $answer->setPetition($petition5);
        $answer->setOptionId(Petition::OPTION_ID_UPVOTE);
        $answer->setCreatedAt(new \DateTime('-2 month'));
        $manager->persist($answer);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadMicropetitionData::class];
    }
}

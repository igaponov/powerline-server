<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

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

        $answer1 = new Answer();
        $answer1->setUser($user);
        $answer1->setPetition($petition1);
        $answer1->setOptionId(Petition::OPTION_ID_UPVOTE);
        $answer1->setCreatedAt(new \DateTime('-2 months'));
        $manager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setUser($user2);
        $answer2->setPetition($petition1);
        $answer2->setOptionId(Petition::OPTION_ID_DOWNVOTE);
        $answer2->setCreatedAt(new \DateTime('-3 months'));
        $manager->persist($answer2);

        $answer3 = new Answer();
        $answer3->setUser($user3);
        $answer3->setPetition($petition1);
        $answer3->setOptionId(Petition::OPTION_ID_IGNORE);
        $answer3->setCreatedAt(new \DateTime('-1 month'));
        $manager->persist($answer3);

        $answer4 = new Answer();
        $answer4->setUser($user1);
        $answer4->setPetition($petition5);
        $answer4->setOptionId(Petition::OPTION_ID_UPVOTE);
        $answer4->setCreatedAt(new \DateTime('-1 month'));
        $manager->persist($answer4);

        $answer5 = new Answer();
        $answer5->setUser($user2);
        $answer5->setPetition($petition5);
        $answer5->setOptionId(Petition::OPTION_ID_UPVOTE);
        $answer5->setCreatedAt(new \DateTime('-2 month'));
        $manager->persist($answer5);

        $manager->flush();

        $this->addReference('micropetition_answer_1', $answer1);
        $this->addReference('micropetition_answer_2', $answer2);
        $this->addReference('micropetition_answer_3', $answer3);
        $this->addReference('micropetition_answer_4', $answer4);
        $this->addReference('micropetition_answer_5', $answer5);
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadMicropetitionData::class];
    }
}

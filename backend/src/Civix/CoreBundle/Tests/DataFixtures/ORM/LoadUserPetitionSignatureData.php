<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\UserPetition\Signature;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserPetitionSignatureData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user3 = $this->getReference('user_3');
        $petition1 = $this->getReference('user_petition_1');
        $petition5 = $this->getReference('user_petition_5');

        $answer1 = new Signature();
        $answer1->setUser($user1);
        $answer1->setPetition($petition1);
        $manager->persist($answer1);

        $answer2 = new Signature();
        $answer2->setUser($user2);
        $answer2->setPetition($petition1);
        $manager->persist($answer2);

        $answer3 = new Signature();
        $answer3->setUser($user3);
        $answer3->setPetition($petition1);
        $manager->persist($answer3);

        $answer4 = new Signature();
        $answer4->setUser($user1);
        $answer4->setPetition($petition5);
        $manager->persist($answer4);

        $answer5 = new Signature();
        $answer5->setUser($user2);
        $answer5->setPetition($petition5);
        $manager->persist($answer5);

        $manager->flush();

        $this->addReference('petition_answer_1', $answer1);
        $this->addReference('petition_answer_2', $answer2);
        $this->addReference('petition_answer_3', $answer3);
        $this->addReference('petition_answer_4', $answer4);
        $this->addReference('petition_answer_5', $answer5);
    }

    public function getDependencies()
    {
        return [LoadUserPetitionData::class];
    }
}

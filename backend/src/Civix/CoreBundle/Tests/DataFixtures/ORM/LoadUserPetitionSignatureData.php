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
        $user4 = $this->getReference('user_4');
        $petition1 = $this->getReference('user_petition_1');
        $petition3 = $this->getReference('user_petition_3');
        $petition5 = $this->getReference('user_petition_5');

        $signature = new Signature();
        $signature->setUser($user1);
        $signature->setPetition($petition1);
        $manager->persist($signature);
        $this->addReference('petition_answer_1', $signature);

        $signature = new Signature();
        $signature->setUser($user2);
        $signature->setPetition($petition1);
        $manager->persist($signature);
        $this->addReference('petition_answer_2', $signature);

        $signature = new Signature();
        $signature->setUser($user3);
        $signature->setPetition($petition1);
        $manager->persist($signature);
        $this->addReference('petition_answer_3', $signature);

        $signature = new Signature();
        $signature->setUser($user1);
        $signature->setPetition($petition5);
        $manager->persist($signature);
        $this->addReference('petition_answer_4', $signature);

        $signature = new Signature();
        $signature->setUser($user2);
        $signature->setPetition($petition5);
        $manager->persist($signature);
        $this->addReference('petition_answer_5', $signature);

        $signature = new Signature();
        $signature->setUser($user3);
        $signature->setPetition($petition3);
        $manager->persist($signature);

        $signature = new Signature();
        $signature->setUser($user4);
        $signature->setPetition($petition3);
        $manager->persist($signature);

        $manager->flush();

    }

    public function getDependencies()
    {
        return [LoadUserPetitionData::class];
    }
}

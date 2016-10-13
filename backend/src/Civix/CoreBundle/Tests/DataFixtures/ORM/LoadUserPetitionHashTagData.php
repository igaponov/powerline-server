<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserPetitionHashTagData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var UserPetition $petition */
        $petition = $this->getReference('user_petition_1');
        $tag1 = $this->getReference('tag_1');
        $tag2 = $this->getReference('tag_2');
        $tag3 = $this->getReference('tag_3');

        $petition->addHashTag($tag1);
        $petition->addHashTag($tag2);
        $petition->addHashTag($tag3);

        $manager->persist($petition);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserPetitionData::class, LoadHashTagData::class];
    }
}

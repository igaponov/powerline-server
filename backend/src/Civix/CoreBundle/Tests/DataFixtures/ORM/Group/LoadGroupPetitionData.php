<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question\GroupPetition;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadGroupPetitionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var Group $group1 */
        $group1 = $this->getReference('group_1');

        $question = new GroupPetition();
        $question->setOwner($group1);
        $question->setUser($group1->getOwner());
        $question->setPetitionTitle('subj1 with #testHashTag '.$faker->sentence);
        $question->setPetitionBody('test body');
        $manager->persist($question);
        $this->addReference('group_petition_1', $question);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
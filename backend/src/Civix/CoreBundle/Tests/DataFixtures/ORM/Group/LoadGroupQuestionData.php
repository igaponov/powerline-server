<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question\Group as GroupQuestion;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadGroupQuestionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $group1 = $this->getReference('group_1');
        $group2 = $this->getReference('group_2');
        $group3 = $this->getReference('group_3');

        $question = new GroupQuestion();
        $question->setOwner($group1);
        $question->setUser($group1->getOwner());
        $question->setSubject('subj with #test-tag '.$faker->sentence);
        $question->setExpireAt(new \DateTime('+1 day'));
        for ($i = 0; $i < 2; $i++) {
            $option = new Option();
            $option->setValue('val '.$faker->word);
            $question->addOption($option);
        }
        $manager->persist($question);
        $this->addReference('group_question_1', $question);

        // expired
        $question = new GroupQuestion();
        $question->setOwner($group2);
        $question->setUser($group2->getOwner());
        $question->setSubject('subj '.$faker->sentence);
        $question->setExpireAt(new \DateTime('-1 day'));
        for ($i = 0; $i < 2; $i++) {
            $option = new Option();
            $option->setValue('val '.$faker->word);
            $question->addOption($option);
        }
        $manager->persist($question);
        $this->addReference('group_question_2', $question);

        // 3 options
        $question = new GroupQuestion();
        $question->setOwner($group3);
        $question->setUser($group3->getOwner());
        $question->setSubject('subj '.$faker->sentence);
        $question->setExpireAt(new \DateTime('+1 week'));
        for ($i = 0; $i < 3; $i++) {
            $option = new Option();
            $option->setValue('val '.$faker->word);
            $question->addOption($option);
        }
        $manager->persist($question);
        $this->addReference('group_question_3', $question);

        // published
        $question = new GroupQuestion();
        $question->setOwner($group3);
        $question->setUser($group3->getOwner());
        $question->setSubject('subj '.$faker->sentence);
        $question->setExpireAt(new \DateTime('+1 month'));
        $question->setPublishedAt(new \DateTime('-1 day'));
        for ($i = 0; $i < 2; $i++) {
            $option = new Option();
            $option->setValue('val '.$faker->word);
            $question->addOption($option);
        }
        $manager->persist($question);
        $this->addReference('group_question_4', $question);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
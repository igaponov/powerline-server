<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Representative;

use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question\Representative as RepresentativeQuestion;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadRepresentativeQuestionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var Representative $representative1 */
        $representative1 = $this->getReference('representative_jb');
        /** @var Representative $representative2 */
        $representative2 = $this->getReference('representative_jt');
        /** @var Representative $representative3 */
        $representative3 = $this->getReference('representative_wc');

        $question = new RepresentativeQuestion();
        $question->setOwner($representative1);
        $question->setUser($representative1->getUser());
        $question->setSubject('subj with #testHashTag '.$faker->sentence);
        for ($i = 0; $i < 2; $i++) {
            $option = new Option();
            $option->setValue('val '.$i);
            $question->addOption($option);
        }
        $representative1->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_question_1', $question);

        // expired
        $question = new RepresentativeQuestion();
        $question->setOwner($representative2);
        $question->setUser($representative2->getUser());
        $question->setSubject('subj '.$faker->sentence);
        for ($i = 0; $i < 2; $i++) {
            $option = new Option();
            $option->setValue('val '.$i);
            $question->addOption($option);
        }
        $manager->persist($question);
        $this->addReference('representative_question_2', $question);

        // 3 options
        $question = new RepresentativeQuestion();
        $question->setOwner($representative3);
        $question->setUser($representative3->getUser());
        $question->setSubject('subj '.$faker->sentence);
        for ($i = 0; $i < 3; $i++) {
            $option = new Option();
            $option->setValue('val '.$i);
            $question->addOption($option);
        }
        $representative3->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_question_3', $question);

        // published
        $question = new RepresentativeQuestion();
        $question->setOwner($representative3);
        $question->setUser($representative3->getUser());
        $question->setSubject('subj '.$faker->sentence);
        $question->setExpireAt(new \DateTime('+1 month'));
        $question->setPublishedAt(new \DateTime('-1 day'));
        for ($i = 0; $i < 2; $i++) {
            $option = new Option();
            $option->setValue('val '.$i);
            $question->addOption($option);
        }
        $representative3->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_question_4', $question);

        // 1 option
        $question = new RepresentativeQuestion();
        $question->setOwner($representative1);
        $question->setUser($representative1->getUser());
        $question->setSubject('subj '.$faker->sentence);
        $question->setExpireAt(new \DateTime('+1 month'));
        $option = new Option();
        $option->setValue('val x');
        $question->addOption($option);
        $representative1->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_question_5', $question);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadRepresentativeData::class];
    }
}
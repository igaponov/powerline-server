<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Representative;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadQuestionAnswerData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    private $manager;

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference(
            'representative_question_answer_1',
            $this->createAnswer(
                $this->getReference('user_2'),
                $this->getReference('representative_question_1')
            )
        );
        $this->addReference(
            'representative_question_answer_2',
            $this->createAnswer(
                $this->getReference('user_3'),
                $this->getReference('representative_question_1')
            )
        );
        $this->addReference(
            'representative_question_answer_3',
            $this->createAnswer(
                $this->getReference('user_4'),
                $this->getReference('representative_question_1')
            )
        );
        $this->addReference(
            'representative_question_answer_4',
            $this->createAnswer(
                $this->getReference('user_4'),
                $this->getReference('representative_question_3')
            )
        );
        $this->createAnswer(
            $this->getReference('user_2'),
            $this->getReference('representative_question_3')
        );
    }

    public function getDependencies()
    {
        return [LoadRepresentativeQuestionData::class];
    }

    /**
     * @param object|User $user
     * @param object|Question $question
     * @return Answer
     */
    private function createAnswer($user, $question)
    {
        $faker = Factory::create();
        $answer = new Answer();
        $answer->setUser($user);
        $answer->setQuestion($question);
        $answer->setComment($faker->text);
        $answer->setOption($faker->randomElement($question->getOptions()->toArray()));
        $answer->setPaymentAmount($faker->randomDigit);

        $this->manager->persist($answer);
        $this->manager->flush();

        return $answer;
    }
}
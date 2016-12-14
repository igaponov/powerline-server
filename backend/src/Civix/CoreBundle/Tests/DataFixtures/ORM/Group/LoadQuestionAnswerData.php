<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadQuestionAnswerData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var ObjectManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference(
            'question_answer_1',
            $this->createAnswer(
                $this->getReference('user_2'),
                $this->getReference('group_question_1')
            )
        );
        $this->addReference(
            'question_answer_2',
            $this->createAnswer(
                $this->getReference('user_3'),
                $this->getReference('group_question_1'),
                Answer::PRIVACY_PRIVATE
            )
        );
        $this->addReference(
            'question_answer_3',
            $this->createAnswer(
                $this->getReference('user_4'),
                $this->getReference('group_question_1')
            )
        );
        $this->addReference(
            'question_answer_4',
            $this->createAnswer(
                $this->getReference('user_4'),
                $this->getReference('group_question_3')
            )
        );
        $this->createAnswer(
            $this->getReference('user_2'),
            $this->getReference('group_question_3')
        );
    }

    public function getDependencies()
    {
        return [LoadGroupQuestionData::class];
    }

    /**
     * @param object|User $user
     * @param object|Question $question
     * @param int $privacy
     * @return Answer
     */
    private function createAnswer($user, $question, $privacy = Answer::PRIVACY_PUBLIC)
    {
        $faker = Factory::create();
        $answer = new Answer();
        $answer->setUser($user);
        $answer->setQuestion($question);
        $answer->setComment($faker->text);
        $answer->setOption($faker->randomElement($question->getOptions()->toArray()));
        $answer->setPaymentAmount($faker->randomDigit);
        $answer->setPrivacy($privacy);

        $this->manager->persist($answer);
        $this->manager->flush();

        return $answer;
    }
}
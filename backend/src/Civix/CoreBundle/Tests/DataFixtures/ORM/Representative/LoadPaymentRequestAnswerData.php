<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Representative;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadPaymentRequestAnswerData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    private $manager;

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference(
            'payment_request_answer_1',
            $this->createAnswer(
                $this->getReference('user_2'),
                $this->getReference('representative_payment_request_1')
            )
        );
        $this->addReference(
            'payment_request_answer_2',
            $this->createAnswer(
                $this->getReference('user_3'),
                $this->getReference('representative_payment_request_1'),
                Answer::PRIVACY_PRIVATE
            )
        );
        $this->addReference(
            'payment_request_answer_3',
            $this->createAnswer(
                $this->getReference('user_4'),
                $this->getReference('representative_payment_request_1')
            )
        );
        $this->addReference(
            'payment_request_answer_4',
            $this->createAnswer(
                $this->getReference('user_1'),
                $this->getReference('representative_payment_request_2')
            )
        );
        $this->createAnswer(
            $this->getReference('user_2'),
            $this->getReference('representative_payment_request_2')
        );
        $this->addReference(
            'payment_request_answer_6',
            $this->createAnswer(
                $this->getReference('user_1'),
                $this->getReference('representative_payment_request_3')
            )
        );
        $this->createAnswer(
            $this->getReference('user_3'),
            $this->getReference('representative_payment_request_3')
        );
    }

    public function getDependencies()
    {
        return [LoadRepresentativePaymentRequestData::class];
    }

    /**
     * @param object|User $user
     * @param object|Question $question
     * @param int $privacy
     * @return Answer
     */
    private function createAnswer($user, $question, $privacy = Answer::PRIVACY_PUBLIC): Answer
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
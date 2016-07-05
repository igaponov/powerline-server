<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Stripe\CustomerGroup;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
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
            'question-answer',
            $this->createAnswer(
                $this->getReference('userfollowtest1'),
                $this->getReference('group-question')
            )
        );
        $this->createAnswer(
            $this->getReference('userfollowtest2'),
            $this->getReference('group-question')
        );
        $this->createAnswer(
            $this->getReference('userfollowtest3'),
            $this->getReference('group-question')
        );
        $this->addReference(
            'testfollowsecretgroups-answer', 
            $this->createAnswer(
                $this->getReference('followertest'),
                $this->getReference('testfollowsecretgroups-question')
            )
        );
        $this->createAnswer(
            $this->getReference('testuserbookmark1'),
            $this->getReference('testfollowsecretgroups-question')
        );
    }

    public function getDependencies()
    {
        return [LoadGroupFollowerTestData::class, LoadGroupQuestionData::class];
    }

    /**
     * @param object|User $user
     * @param object|Question $question
     * @return CustomerGroup
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
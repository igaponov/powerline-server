<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question\Group as GroupQuestion;
use Civix\CoreBundle\Entity\Stripe\CustomerGroup;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadGroupQuestionData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
            'group-question',
            $this->createQuestion($this->getReference('group'))
        );
        $this->createQuestion($this->getReference('group'));
        $this->createQuestion($this->getReference('group'));
        $this->addReference(
            'testfollowsecretgroups-question', 
            $this->createQuestion($this->getReference('testfollowsecretgroups'))
        );
        $this->createQuestion($this->getReference('testfollowprivategroups'));
    }

    public function getDependencies()
    {
        return [LoadGroupFollowerTestData::class];
    }

    /**
     * @param $group
     * @return CustomerGroup
     */
    private function createQuestion($group)
    {
        $faker = Factory::create();
        $question = new GroupQuestion();
        $question->setUser($group);
        $question->setSubject('subj '.$faker->sentence);
        $question->setExpireAt(new \DateTime('+1 day'));
        for ($i = 0; $i < 2; $i++) {
            $option = new Option();
            $option->setValue('val '.$faker->word);
            $question->addOption($option);
        }

        $this->manager->persist($question);
        $this->manager->flush();

        return $question;
    }
}
<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question\GroupPaymentRequest;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadGroupPaymentRequestData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var Group $group1 */
        $group1 = $this->getReference('group_1');

        $question = new GroupPaymentRequest();
        $question->setOwner($group1);
        $question->setUser($group1->getOwner());
        $question->setTitle('subj with #test-tag '.$faker->sentence);
        $question->setIsAllowOutsiders(true);
        $question->setIsCrowdfunding(true);

        $option = new Option();
        $option->setValue('val '.$faker->word)
            ->setIsUserAmount(true);
        $question->addOption($option);

        $option = new Option();
        $option->setValue('val '.$faker->word)
            ->setIsUserAmount(false)
            ->setPaymentAmount(400);
        $question->addOption($option);

        $group1->getOwner()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('group_payment_request_1', $question);

        $question = new GroupPaymentRequest();
        $question->setOwner($group1);
        $question->setUser($group1->getOwner());
        $question->setTitle('subj with #test-tag '.$faker->sentence);
        $question->setIsAllowOutsiders(false);
        $question->setIsCrowdfunding(false);

        $option = new Option();
        $option->setValue('val '.$faker->word)
            ->setIsUserAmount(true);
        $question->addOption($option);

        $option = new Option();
        $option->setValue('val '.$faker->word)
            ->setIsUserAmount(false)
            ->setPaymentAmount(500);
        $question->addOption($option);

        $group1->getOwner()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('group_payment_request_2', $question);

        $question = new GroupPaymentRequest();
        $question->setOwner($group1);
        $question->setUser($group1->getOwner());
        $question->setTitle('subj with #test-tag '.$faker->sentence);
        $question->setIsAllowOutsiders(true);
        $question->setIsCrowdfunding(true);
        $question->setCrowdfundingDeadline(new \DateTime('-1 day'));

        $option = new Option();
        $option->setValue('val '.$faker->word)
            ->setIsUserAmount(false)
            ->setPaymentAmount(400);
        $question->addOption($option);

        $option = new Option();
        $option->setValue('val '.$faker->word)
            ->setIsUserAmount(true);
        $question->addOption($option);

        $group1->getOwner()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('group_payment_request_3', $question);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
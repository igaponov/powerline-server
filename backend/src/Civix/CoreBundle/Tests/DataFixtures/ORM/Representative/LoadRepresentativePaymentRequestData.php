<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Representative;

use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question\RepresentativePaymentRequest;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadRepresentativePaymentRequestData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var UserRepresentative $representative */
        $representative = $this->getReference('representative_jb');

        $question = new RepresentativePaymentRequest();
        $question->setOwner($representative);
        $question->setUser($representative->getUser());
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
        /** @noinspection NullPointerExceptionInspection */
        $representative->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_payment_request_1', $question);

        $question = new RepresentativePaymentRequest();
        $question->setOwner($representative);
        $question->setUser($representative->getUser());
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
        /** @noinspection NullPointerExceptionInspection */
        $representative->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_payment_request_2', $question);

        $question = new RepresentativePaymentRequest();
        $question->setOwner($representative);
        $question->setUser($representative->getUser());
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
        /** @noinspection NullPointerExceptionInspection */
        $representative->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_payment_request_3', $question);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserRepresentativeData::class];
    }
}
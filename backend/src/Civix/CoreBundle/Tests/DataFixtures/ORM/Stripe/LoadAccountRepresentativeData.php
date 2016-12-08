<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadAccountRepresentativeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Representative $representative */
        $representative = $this->getReference('representative_jb');

        $account = new Account();
        $account->setId('65DAC0B12')
            ->setSecretKey('SECRET123')
            ->setPublishableKey('PUB0KEY598')
            ->updateBankAccounts([
                (object)[
                    'id' => 'acc0',
                    'last4' => '1234',
                    'bank_name' => 'EU Bank Name',
                    'country' => 'DE',
                    'currency' => 'eur',
                ]
            ]);
        $representative->setStripeAccount($account);
        $manager->persist($representative);
        $this->addReference('representative_account_1', $account);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadRepresentativeData::class];
    }
}
<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\AccountGroup;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadAccountGroupData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $group = $this->getReference('group_1');

        $account = new AccountGroup();
        $account->setUser($group)
            ->setStripeId('65DAC0B12')
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
        $manager->persist($account);
        $this->addReference('stripe_account_1', $account);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
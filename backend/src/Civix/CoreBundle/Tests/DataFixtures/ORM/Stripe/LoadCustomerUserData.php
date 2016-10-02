<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe;

use Civix\CoreBundle\Entity\Stripe\CustomerUser;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadCustomerUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user = $this->getReference('user_1');

        $account = new CustomerUser();
        $account->setUser($user)
            ->setStripeId('65DAC0B12')
            ->updateCards([
                (object)[
                    'id' => 'acc0',
                    'last4' => '1234',
                    'brand' => 'EU Bank Name',
                    'funding' => 'xxx',
                ]
            ]);
        $manager->persist($account);
        $this->addReference('stripe_customer_user_1', $account);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
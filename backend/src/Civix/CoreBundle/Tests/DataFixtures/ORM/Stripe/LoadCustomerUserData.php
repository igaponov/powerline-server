<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe;

use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadCustomerUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_1');

        $customer = new Customer();
        $customer->setId('65DAC0B12')
            ->updateCards([
                (object)[
                    'id' => 'acc0',
                    'last4' => '1234',
                    'brand' => 'EU Bank Name',
                    'funding' => 'xxx',
                ]
            ]);
        $user->setStripeCustomer($customer);
        $manager->persist($user);
        $this->addReference('stripe_customer_user_1', $customer);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
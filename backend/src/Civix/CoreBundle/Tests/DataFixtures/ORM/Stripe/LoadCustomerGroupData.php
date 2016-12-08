<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadCustomerGroupData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Group $group */
        $group = $this->getReference('group_1');

        $customer = new Customer();
        $customer->setId('65DAC0B12')
            ->updateCards([
                (object)[
                    'id' => 'acc1',
                    'last4' => '5678',
                    'brand' => 'EU Bank Group Name',
                    'funding' => 'xxxx',
                ]
            ]);
        $group->setStripeCustomer($customer);
        $manager->persist($group);
        $this->addReference('stripe_customer_group_1', $customer);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
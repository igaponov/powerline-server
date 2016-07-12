<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Customer\Customer;
use Civix\CoreBundle\Entity\Customer\CustomerGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadCustomerGroupData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $group1 = $this->getReference('group_1');
        $group2 = $this->getReference('group_2');
        $group3 = $this->getReference('group_3');
        $group4 = $this->getReference('group_4');

        $customer = $this->createCustomer($group1, Customer::ACCOUNT_TYPE_BUSINESS);
        $manager->persist($customer);
        $this->addReference('customer_1', $customer);

        $customer = $this->createCustomer($group2, Customer::ACCOUNT_TYPE_PERSONAL);
        $manager->persist($customer);
        $this->addReference('customer_2', $customer);

        $customer = $this->createCustomer($group3, Customer::ACCOUNT_TYPE_PERSONAL);
        $manager->persist($customer);
        $this->addReference('customer_3', $customer);

        $customer = $this->createCustomer($group4, Customer::ACCOUNT_TYPE_BUSINESS);
        $manager->persist($customer);
        $this->addReference('customer_4', $customer);

        $manager->flush();
    }

    /**
     * @param $group
     * @param $accountType
     * @return CustomerGroup
     */
    private function createCustomer($group, $accountType)
    {
        $faker = Factory::create();
        
        $customer = new CustomerGroup();
        $customer->setBalancedUri($faker->url);
        $customer->setAccountType($accountType);
        $customer->setUser($group);

        return $customer;
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
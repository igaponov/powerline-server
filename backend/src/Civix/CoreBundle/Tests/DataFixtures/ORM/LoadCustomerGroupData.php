<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Customer\Customer;
use Civix\CoreBundle\Entity\Customer\CustomerGroup;
use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCustomerGroupData extends AbstractFixture implements ContainerAwareInterface
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

        $this->createCustomer(
            $this->getReference('group'), 
            Customer::ACCOUNT_TYPE_BUSINESS
        );
        $this->createCustomer(
            $this->getReference('testfollowsecretgroups'), 
            Customer::ACCOUNT_TYPE_PERSONAL
        );
        $this->createCustomer(
            $this->getReference('testfollowprivategroups'), 
            Customer::ACCOUNT_TYPE_PERSONAL
        );
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
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

        $this->manager->persist($customer);
        $this->manager->flush();

        return $customer;
    }
}
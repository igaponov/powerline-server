<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadStripeCustomerGroupData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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

        $this->createCustomer($this->getReference('group'));
        $this->createCustomer($this->getReference('testfollowsecretgroups'));
        $this->createCustomer($this->getReference('testfollowprivategroups'));
    }

    public function getDependencies()
    {
        return [LoadGroupFollowerTestData::class];
    }
    
    /**
     * @param object|Group $group
     * @return Customer
     */
    private function createCustomer(Group $group)
    {
        $customer = new Customer();
        $customer->setId(uniqid());
        $group->setStripeCustomer($customer);

        $this->manager->persist($group);
        $this->manager->flush();

        return $customer;
    }
}
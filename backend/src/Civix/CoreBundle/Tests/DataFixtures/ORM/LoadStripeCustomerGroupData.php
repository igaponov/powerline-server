<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\CustomerGroup;
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
     * @param $group
     * @return CustomerGroup 
     */
    private function createCustomer($group)
    {
        $customer = new CustomerGroup();
        $customer->setStripeId(uniqid());
        $customer->setUser($group);

        $this->manager->persist($customer);
        $this->manager->flush();

        return $customer;
    }
}
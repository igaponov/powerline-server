<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadStripeCustomerUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');

        $customer = new Customer();
        $customer->setId(uniqid());
        $user1->setStripeCustomer($customer);

        $manager->persist($user1);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
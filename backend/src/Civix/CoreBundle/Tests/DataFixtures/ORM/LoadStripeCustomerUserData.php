<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Stripe\CustomerUser;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadStripeCustomerUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('user_1');

        $customer = new CustomerUser();
        $customer->setStripeId(uniqid());
        $customer->setUser($user1);

        $manager->persist($customer);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Subscription\Subscription;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadSubscriptionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $group1 = $this->getReference('group_1');
        $group2 = $this->getReference('group_2');
        $group3 = $this->getReference('group_3');
        $group4 = $this->getReference('group_4');

        $subscription = $this->createSubscription($group1, Subscription::PACKAGE_TYPE_PLATINUM);
        $manager->persist($subscription);
        $this->addReference('subscription_1', $subscription);

        $this->createSubscription($group2, Subscription::PACKAGE_TYPE_FREE);
        $manager->persist($subscription);
        $this->addReference('subscription_2', $subscription);

        $this->createSubscription($group3, Subscription::PACKAGE_TYPE_COMMERCIAL);
        $manager->persist($subscription);
        $this->addReference('subscription_3', $subscription);

        $this->createSubscription($group4, Subscription::PACKAGE_TYPE_PLATINUM);
        $manager->persist($subscription);
        $this->addReference('subscription_4', $subscription);

        $this->createSubscription($group4, Subscription::PACKAGE_TYPE_FREE, false);
        $manager->persist($subscription);
        $this->addReference('subscription_5', $subscription);

        $manager->flush();
    }

    /**
     * @param $group
     * @param int $packageType
     * @param bool $enabled
     * @return Subscription
     */
    private function createSubscription($group, $packageType, $enabled = true)
    {
        $subscription = new Subscription();
        $subscription->setStripeId(uniqid());
        $subscription->setUserEntity($group);
        $subscription->setEnabled($enabled);
        $subscription->setPackageType($packageType);
        $subscription->setExpiredAt(new \DateTime('+1 year'));
        $subscription->setNextPaymentAt(new \DateTime('+1 month'));
        $subscription->setStripeSyncAt(new \DateTime('+1 day'));

        return $subscription;
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
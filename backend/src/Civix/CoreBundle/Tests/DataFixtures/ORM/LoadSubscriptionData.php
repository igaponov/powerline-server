<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\CustomerGroup;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSubscriptionData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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

        $this->createSubscription($this->getReference('group'), Subscription::PACKAGE_TYPE_PLATINUM);
        $this->createSubscription($this->getReference('testfollowsecretgroups'), Subscription::PACKAGE_TYPE_FREE);
        $this->createSubscription($this->getReference('testfollowprivategroups'), Subscription::PACKAGE_TYPE_COMMERCIAL);
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }

    /**
     * @param $group
     * @param int $packageType
     * @return CustomerGroup
     */
    private function createSubscription($group, $packageType)
    {
        $subscription = new Subscription();
        $subscription->setStripeId(uniqid());
        $subscription->setUserEntity($group);
        $subscription->setEnabled(true);
        $subscription->setPackageType($packageType);
        $subscription->setExpiredAt(new \DateTime('+1 year'));
        $subscription->setNextPaymentAt(new \DateTime('+1 month'));
        $subscription->setStripeSyncAt(new \DateTime('+1 day'));

        $this->manager->persist($subscription);
        $this->manager->flush();

        return $subscription;
    }
}
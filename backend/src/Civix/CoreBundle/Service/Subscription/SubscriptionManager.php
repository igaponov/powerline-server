<?php

namespace Civix\CoreBundle\Service\Subscription;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManager;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Civix\CoreBundle\Model\Subscription\Package;

class SubscriptionManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Stripe
     */
    private $stripe;

    private $packageKeyToClass = [
        Subscription::PACKAGE_TYPE_FREE => 'Free',
        Subscription::PACKAGE_TYPE_SILVER => 'Silver',
        Subscription::PACKAGE_TYPE_GOLD => 'Gold',
        Subscription::PACKAGE_TYPE_PLATINUM => 'Platinum',
        Subscription::PACKAGE_TYPE_COMMERCIAL => 'Commercial',
    ];

    private $prices = [
        Subscription::PACKAGE_TYPE_FREE => 0,
        Subscription::PACKAGE_TYPE_SILVER => 19,
        Subscription::PACKAGE_TYPE_GOLD => 39,
        Subscription::PACKAGE_TYPE_PLATINUM => 125,
        Subscription::PACKAGE_TYPE_COMMERCIAL => null,
    ];

    public function __construct(EntityManager $em, Stripe $stripe)
    {
        $this->em = $em;
        $this->stripe = $stripe;
    }

    /**
     * @param UserInterface $user
     *
     * @return Subscription
     */
    public function getSubscription(UserInterface $user)
    {
        $subscription = $this->em->getRepository(Subscription::class)->findOneBy([
            $user->getType() => $user,
        ]);

        if (!$subscription) {
            $subscription = new Subscription();
            $subscription
                ->setPackageType(Subscription::PACKAGE_TYPE_FREE)
                ->setUserEntity($user)
            ;
        } elseif ($subscription->isSyncNeeded()) {
            return $this->stripe->syncSubscription($subscription);
        }

        return $subscription;
    }

    /**
     * @param UserInterface $user
     *
     * @return Package\Package
     */
    public function getPackage(UserInterface $user)
    {
        $subscription = $this->getSubscription($user);

        if ($subscription->isActive()) {
            return $this->createPackageObject($subscription->getPackageType());
        } else {
            return $this->createPackageObject($subscription::PACKAGE_TYPE_FREE);
        }
    }

    public function getPackagesInfo(Subscription $subscription)
    {
        $isCommercialAccount = $subscription->getGroup() && $subscription->getGroup()->isCommercial();

        $memberCount = 0;
        if ($subscription->getGroup()) {
            $memberCount = $subscription->getGroup()->getTotalMembers();
        }

        return [
            Subscription::PACKAGE_TYPE_FREE => [
                'title' => Subscription::$labels[Subscription::PACKAGE_TYPE_FREE],
                'price' => $this->prices[Subscription::PACKAGE_TYPE_FREE],
                'isBuyAvailable' => false,
            ],
            Subscription::PACKAGE_TYPE_SILVER => [
                'title' => Subscription::$labels[Subscription::PACKAGE_TYPE_SILVER],
                'price' => $this->prices[Subscription::PACKAGE_TYPE_SILVER],
                'isBuyAvailable' => $subscription->getPackageType() <= Subscription::PACKAGE_TYPE_SILVER
                    && !$isCommercialAccount
                    && $memberCount < $this->createPackageObject($subscription::PACKAGE_TYPE_SILVER)
                        ->getGroupSizeLimitation(),
            ],
            Subscription::PACKAGE_TYPE_GOLD => [
                'title' => Subscription::$labels[Subscription::PACKAGE_TYPE_GOLD],
                'price' => $this->prices[Subscription::PACKAGE_TYPE_GOLD],
                'isBuyAvailable' => $subscription->getPackageType() <= Subscription::PACKAGE_TYPE_GOLD
                    && $memberCount < $this->createPackageObject($subscription::PACKAGE_TYPE_GOLD)
                        ->getGroupSizeLimitation(),
            ],
            Subscription::PACKAGE_TYPE_PLATINUM => [
                'title' => Subscription::$labels[Subscription::PACKAGE_TYPE_PLATINUM],
                'price' => $this->prices[Subscription::PACKAGE_TYPE_PLATINUM],
                'isBuyAvailable' => $subscription->getPackageType() <= Subscription::PACKAGE_TYPE_PLATINUM
                    && !$isCommercialAccount,
            ],
            Subscription::PACKAGE_TYPE_COMMERCIAL => [
                'title' => Subscription::$labels[Subscription::PACKAGE_TYPE_COMMERCIAL],
                'price' => $this->prices[Subscription::PACKAGE_TYPE_COMMERCIAL],
                'isBuyAvailable' => false,
            ],
        ];
    }

    public function getPackagePrice($packageType)
    {
        return $this->prices[$packageType];
    }

    /**
     * @param $typeId
     *
     * @return Package\Package
     */
    private function createPackageObject($typeId)
    {
        $class = '\\Civix\\CoreBundle\\Model\\Subscription\\Package\\'.
            $this->packageKeyToClass[$typeId];

        return new $class();
    }

    public function subscribe(Subscription $subscription)
    {
        return $this->stripe->handleSubscription($subscription);
    }

    public function unsubscribe(Subscription $subscription)
    {
        return $this->stripe->cancelSubscription($subscription);
    }
}

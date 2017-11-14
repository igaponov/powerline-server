<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Event\DiscountCodeEvent;
use Civix\CoreBundle\Event\DiscountCodeEvents;
use Civix\CoreBundle\Event\SubscriptionEvent;
use Civix\CoreBundle\Event\SubscriptionEvents;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\Stripe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DiscountCodeSubscriber implements EventSubscriberInterface
{
    const REFERRAL_COUNT = 3;

    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var Stripe
     */
    private $stripe;
    /**
     * @var string
     */
    private $referralCode;

    public static function getSubscribedEvents(): array
    {
        return [
            SubscriptionEvents::SUBSCRIBE => 'addRewardCode',
            UserEvents::REGISTRATION => 'addDiscountCode',
            UserEvents::LEGACY_REGISTRATION => 'addDiscountCode',
        ];
    }

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        Stripe $stripe,
        string $referralCode
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->stripe = $stripe;
        $this->referralCode = $referralCode;
    }

    public function addDiscountCode(UserEvent $event): void
    {
        $user = $event->getUser();
        $discountCode = new DiscountCode($this->referralCode, $user);
        $this->em->persist($discountCode);
        $this->em->flush();
    }

    public function addRewardCode(SubscriptionEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $coupon = $event->getSubscription()->getCoupon();
        $user = $this->tokenStorage->getToken()->getUser();

        if (!($code = $coupon->getDiscountCode()) instanceof DiscountCode || !$user) {
            return;
        }

        $code->use($user);
        $this->em->persist($code);
        if ($code->getOwner() && $code->getUsesCount() % self::REFERRAL_COUNT === 0) {
            $rewardCode = new DiscountCode($this->stripe->createCoupon($code->getOwner()));
            $this->em->persist($rewardCode);

            $event = new DiscountCodeEvent($rewardCode, $code->getOwner());
            $dispatcher->dispatch(DiscountCodeEvents::CREATE, $event);
        }
        $this->em->flush();
    }
}
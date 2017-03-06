<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Subscription\Subscription;
use Symfony\Component\EventDispatcher\Event;

class SubscriptionEvent extends Event
{
    /**
     * @var Subscription
     */
    private $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }
}
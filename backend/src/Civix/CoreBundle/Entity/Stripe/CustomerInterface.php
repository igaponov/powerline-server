<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\OfficialInterface;

interface CustomerInterface
{
    /**
     * @return OfficialInterface
     */
    public function getUser();

    /**
     * @param OfficialInterface
     */
    public function setUser();

    public function setStripeId($stripeId);

    public function getStripeId();

    public function getCards();

    public function updateCards($cards);
}

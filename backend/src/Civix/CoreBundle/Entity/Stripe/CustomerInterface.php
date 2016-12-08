<?php

namespace Civix\CoreBundle\Entity\Stripe;

interface CustomerInterface
{
    public function setId($stripeId);

    public function getId();

    public function getCards();

    public function updateCards($cards);
}

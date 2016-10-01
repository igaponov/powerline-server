<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\UserInterface;

interface AccountInterface
{
    /**
     * @return UserInterface
     */
    public function getUser();

    /**
     * @return AccountInterface
     */
    public function setUser();

    /**
     * @return AccountInterface
     */
    public function setStripeId($stripeId);

    public function getStripeId();

    public function getSecretKey();

    /**
     * @return AccountInterface
     */
    public function setSecretKey($secretKey);

    public function getPublishableKey();

    /**
     * @return AccountInterface
     */
    public function setPublishableKey($publishableKey);

    public function getBankAccounts();

    public function updateBankAccounts($bankAccounts);
}

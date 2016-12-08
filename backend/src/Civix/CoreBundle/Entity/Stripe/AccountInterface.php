<?php

namespace Civix\CoreBundle\Entity\Stripe;

interface AccountInterface
{
    /**
     * @return AccountInterface
     */
    public function setId($stripeId);

    public function getId();

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

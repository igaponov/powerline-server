<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;

interface AccountInterface
{
    /**
     * @return LeaderContentRootInterface
     */
    public function getRoot();

    /**
     * @param LeaderContentRootInterface $root
     * @return AccountInterface
     */
    public function setRoot(LeaderContentRootInterface $root);

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

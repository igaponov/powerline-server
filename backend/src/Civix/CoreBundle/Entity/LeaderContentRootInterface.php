<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\Account;

interface LeaderContentRootInterface
{
    /**
     * @return integer
     */
    public function getId();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return User
     */
    public function getUser();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getOfficialTitle();

    /**
     * @return Account
     */
    public function getStripeAccount();

    /**
     * @param Account $account
     * @return $this
     */
    public function setStripeAccount(Account $account);
}
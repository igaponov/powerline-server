<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\AccountInterface;

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
     * @return AccountInterface
     */
    public function getStripeAccount();

    /**
     * @param AccountInterface $account
     * @return $this
     */
    public function setStripeAccount(AccountInterface $account);
}
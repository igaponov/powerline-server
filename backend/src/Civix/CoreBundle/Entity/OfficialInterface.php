<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\CustomerInterface;

interface OfficialInterface
{
    /**
     * @return string
     */
    public function getOfficialName();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return CustomerInterface
     */
    public function getStripeCustomer();

    /**
     * @param CustomerInterface $customer
     * @return $this
     */
    public function setStripeCustomer(CustomerInterface $customer);
}
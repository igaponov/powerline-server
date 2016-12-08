<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\Customer;

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
     * @return Customer
     */
    public function getStripeCustomer();

    /**
     * @param Customer $customer
     * @return $this
     */
    public function setStripeCustomer(Customer $customer);
}
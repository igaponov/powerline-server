<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\Customer;
use Doctrine\ORM\Mapping as ORM;

trait HasStripeCustomerTrait
{
    /**
     * @var Customer
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\Customer", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $stripeCustomer;

    /**
     * @return Customer
     */
    public function getStripeCustomer()
    {
        return $this->stripeCustomer;
    }

    /**
     * @param Customer $stripeCustomer
     * @return $this
     */
    public function setStripeCustomer(Customer $stripeCustomer)
    {
        $this->stripeCustomer = $stripeCustomer;

        return $this;
    }
}
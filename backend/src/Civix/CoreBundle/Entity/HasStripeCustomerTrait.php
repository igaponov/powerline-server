<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\CustomerInterface;
use Doctrine\ORM\Mapping as ORM;

trait HasStripeCustomerTrait
{
    /**
     * @var CustomerInterface
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\CustomerInterface", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $stripeCustomer;

    /**
     * @return CustomerInterface
     */
    public function getStripeCustomer()
    {
        return $this->stripeCustomer;
    }

    /**
     * @param CustomerInterface $stripeCustomer
     * @return $this
     */
    public function setStripeCustomer(CustomerInterface $stripeCustomer)
    {
        $this->stripeCustomer = $stripeCustomer;

        return $this;
    }
}
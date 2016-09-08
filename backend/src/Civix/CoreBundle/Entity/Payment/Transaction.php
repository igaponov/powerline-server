<?php

namespace Civix\CoreBundle\Entity\Payment;

use Civix\CoreBundle\Entity\Stripe\Customer;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payments_transaction")
 */
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="reference", type="string", length=255, unique=true)
     */
    private $referencePayment;

    /**
     * @ORM\JoinColumn(name="stripe_customer_id", nullable=false, onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\Customer")
     */
    private $stripeCustomer;

    /**
     * @ORM\Column(name="data", type="text")
     */
    private $data;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set referencePayment.
     *
     * @param string $referencePayment
     *
     * @return Transaction
     */
    public function setReferencePayment($referencePayment)
    {
        $this->referencePayment = $referencePayment;

        return $this;
    }

    /**
     * Get referencePayment.
     *
     * @return string
     */
    public function getReferencePayment()
    {
        return $this->referencePayment;
    }

    /**
     * Set data.
     *
     * @param string $data
     *
     * @return Transaction
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    public function setStripeCustomer(Customer $customer)
    {
        $this->stripeCustomer = $customer;

        return $this;
    }

    public function getStripeCustomer()
    {
        return $this->stripeCustomer;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Transaction
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}

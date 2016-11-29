<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\Poll\Question;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Stripe\ChargeRepository")
 * @ORM\Table(name="stripe_charges")
 */
class Charge
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="stripe_id", type="string")
     */
    private $stripeId;

    /**
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(name="application_fee", type="integer", nullable=true)
     */
    private $applicationFee;

    /**
     * @ORM\Column(name="currency", type="string", length=3)
     */
    private $currency;

    /**
     * @ORM\Column(name="receipt_number", type="string", nullable=true)
     */
    private $receiptNumber;

    /**
     * @ORM\Column(name="created", type="integer")
     */
    private $created;

    /**
     * @var Question
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Poll\Question")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $question;

    /**
     * @ORM\JoinColumn(name="from_customer", nullable=false)
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\Customer")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $fromCustomer;

    /**
     * @ORM\JoinColumn(name="to_account")
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\Account")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $toAccount;

    public function __construct(Customer $customer, Account $account = null,
                                Question $question = null)
    {
        $this->fromCustomer = $customer;
        $this->toAccount = $account;
        $this->question = $question;
    }

    /**
     * @param \Stripe\Charge $sc
     * @deprecated
     */
    public function updateStripeData(\Stripe\Charge $sc)
    {
        $this->stripeId = $sc->id;
        $this->status = $sc->status;
        $this->amount = $sc->amount;
        $this->currency = $sc->currency;
        $this->applicationFee = $sc->application_fee;
        $this->receiptNumber = $sc->receipt_number;
        $this->created = $sc->created;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isSucceeded()
    {
        return $this->status === 'succeeded';
    }

    /**
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @return mixed
     */
    public function getFromCustomer()
    {
        return $this->fromCustomer;
    }

    /**
     * @return mixed
     */
    public function getToAccount()
    {
        return $this->toAccount;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     * @return Charge
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStripeId()
    {
        return $this->stripeId;
    }

    /**
     * @param mixed $stripeId
     * @return Charge
     */
    public function setStripeId($stripeId)
    {
        $this->stripeId = $stripeId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return Charge
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getApplicationFee()
    {
        return $this->applicationFee;
    }

    /**
     * @param mixed $applicationFee
     * @return Charge
     */
    public function setApplicationFee($applicationFee)
    {
        $this->applicationFee = $applicationFee;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     * @return Charge
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReceiptNumber()
    {
        return $this->receiptNumber;
    }

    /**
     * @param mixed $receiptNumber
     * @return Charge
     */
    public function setReceiptNumber($receiptNumber)
    {
        $this->receiptNumber = $receiptNumber;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     * @return Charge
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    public function toArray()
    {
        return [
            'receipt_number' => $this->receiptNumber,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'created' => $this->created,
        ];
    }
}

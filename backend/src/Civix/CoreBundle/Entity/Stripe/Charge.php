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
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="stripe_id", type="string")
     */
    private $stripeId;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

    /**
     * @var int
     *
     * @ORM\Column(name="application_fee", type="integer", nullable=true)
     */
    private $applicationFee;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="receipt_number", type="string", nullable=true)
     */
    private $receiptNumber;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer")
     */
    private $created;

    /**
     * @var Question
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Poll\Question")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $question;

    /**
     * @var Customer
     *
     * @ORM\JoinColumn(name="from_customer", nullable=false)
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\Customer")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $fromCustomer;

    /**
     * @var Account|null
     *
     * @ORM\JoinColumn(name="to_account")
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\Account")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $toAccount;

    public function __construct(Customer $customer, Account $account = null, Question $question = null)
    {
        $this->fromCustomer = $customer;
        $this->toAccount = $account;
        $this->question = $question;
    }

    /**
     * @param \Stripe\Charge|\stdClass $sc
     * @deprecated
     */
    public function updateStripeData(\Stripe\Charge $sc): void
    {
        $this->stripeId = $sc->id;
        $this->status = $sc->status;
        $this->amount = $sc->amount;
        $this->currency = $sc->currency;
        $this->applicationFee = $sc->application_fee;
        $this->receiptNumber = $sc->receipt_number;
        $this->created = $sc->created;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    /**
     * @return Question
     */
    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    /**
     * @return Customer
     */
    public function getFromCustomer(): ?Customer
    {
        return $this->fromCustomer;
    }

    /**
     * @return Account|null
     */
    public function getToAccount(): ?Account
    {
        return $this->toAccount;
    }

    /**
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return Charge
     */
    public function setAmount(int $amount): Charge
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getStripeId(): ?string
    {
        return $this->stripeId;
    }

    /**
     * @param string $stripeId
     * @return Charge
     */
    public function setStripeId(string $stripeId): Charge
    {
        $this->stripeId = $stripeId;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Charge
     */
    public function setStatus(string $status): Charge
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getApplicationFee(): ?int
    {
        return $this->applicationFee;
    }

    /**
     * @param int $applicationFee
     * @return Charge
     */
    public function setApplicationFee(int $applicationFee): Charge
    {
        $this->applicationFee = $applicationFee;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Charge
     */
    public function setCurrency(string $currency): Charge
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    /**
     * @param string $receiptNumber
     * @return Charge
     */
    public function setReceiptNumber(string $receiptNumber): Charge
    {
        $this->receiptNumber = $receiptNumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreated(): ?int
    {
        return $this->created;
    }

    /**
     * @param int $created
     * @return Charge
     */
    public function setCreated(int $created): Charge
    {
        $this->created = $created;

        return $this;
    }

    public function toArray(): array
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

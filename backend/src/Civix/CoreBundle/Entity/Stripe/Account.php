<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Stripe\AccountRepository")
 * @ORM\Table(name="stripe_accounts")
 * @Serializer\ExclusionPolicy("ALL")
 */
class Account implements AccountInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="string")
     */
    private $id;

    /**
     * @ORM\Column(name="secret_key", type="string")
     */
    private $secretKey;

    /**
     * @ORM\Column(name="publishable_key", type="string")
     */
    private $publishableKey;

    /**
     * @ORM\Column(name="bank_accounts", type="json_array", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Type("array")
     */
    private $bankAccounts;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @param mixed $secretKey
     *
     * @return $this
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishableKey()
    {
        return $this->publishableKey;
    }

    /**
     * @param mixed $publishableKey
     *
     * @return $this
     */
    public function setPublishableKey($publishableKey)
    {
        $this->publishableKey = $publishableKey;

        return $this;
    }

    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    public function updateBankAccounts($bankAccounts)
    {
        $this->bankAccounts = array_map(function ($bankAccount) {
            return [
                'id' => $bankAccount->id,
                'last4' => $bankAccount->last4,
                'bank_name' => $bankAccount->bank_name,
                'country' => $bankAccount->country,
                'currency' => $bankAccount->currency,
            ];
        }, $bankAccounts);
    }
}

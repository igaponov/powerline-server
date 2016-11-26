<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Stripe\AccountRepository")
 * @ORM\Table(name="stripe_accounts")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "representative"  = "Civix\CoreBundle\Entity\Stripe\AccountRepresentative",
 *      "group"  = "Civix\CoreBundle\Entity\Stripe\AccountGroup"
 * })
 * @Serializer\ExclusionPolicy("ALL")
 */
abstract class Account implements AccountInterface
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
    public function getStripeId()
    {
        return $this->stripeId;
    }

    /**
     * @param mixed $stripeId
     *
     * @return $this
     */
    public function setStripeId($stripeId)
    {
        $this->stripeId = $stripeId;

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

    public static function getEntityClassByUser(LeaderContentRootInterface $user)
    {
        $type = ucfirst($user->getType());

        return "Civix\\CoreBundle\\Entity\\Stripe\\Account{$type}";
    }
}

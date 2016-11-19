<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\OfficialInterface;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Stripe\CustomerRepository")
 * @ORM\Table(name="stripe_customers")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "group"  = "Civix\CoreBundle\Entity\Stripe\CustomerGroup",
 *      "user"  = "Civix\CoreBundle\Entity\Stripe\CustomerUser"
 * })
 */
abstract class Customer implements CustomerInterface
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
     * @ORM\Column(name="cards", type="json_array", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Type("array")
     */
    private $cards;

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

    public function getCards()
    {
        return $this->cards;
    }

    public function updateCards($cards)
    {
        $this->cards = array_map(function ($card) {
            return [
                'id' => $card->id,
                'last4' => $card->last4,
                'brand' => $card->brand,
                'funding' => $card->funding,
            ];
        }, $cards);
    }

    public static function getEntityClassByUser(OfficialInterface $official)
    {
        $class = get_class($official);
        switch ($class) {
            case User::class:
                $type = CustomerUser::class;
                break;
            case Group::class:
                $type = CustomerGroup::class;
                break;
            default:
                throw new \RuntimeException('Invalid object with class '.$class);
        }

        return $type;
    }
}

<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Stripe\CustomerRepository")
 * @ORM\Table(name="stripe_customers")
 */
class Customer implements CustomerInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="string")
     */
    private $id;

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
}

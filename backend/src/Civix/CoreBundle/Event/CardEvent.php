<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Stripe\CustomerInterface;
use Civix\CoreBundle\Entity\Stripe\Card;

class CardEvent extends CustomerEvent
{
    /**
     * @var Card
     */
    private $card;

    public function __construct(CustomerInterface $customer, Card $card)
    {
        parent::__construct($customer);
        $this->card = $card;
    }

    /**
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }
}
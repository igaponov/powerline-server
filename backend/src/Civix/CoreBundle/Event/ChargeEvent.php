<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Stripe\Charge;
use Symfony\Component\EventDispatcher\Event;

class ChargeEvent extends Event
{
    /**
     * @var Charge
     */
    private $charge;

    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }

    /**
     * @return Charge
     */
    public function getCharge()
    {
        return $this->charge;
    }
}
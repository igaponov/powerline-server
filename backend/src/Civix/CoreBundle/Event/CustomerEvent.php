<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Stripe\CustomerInterface;
use Symfony\Component\EventDispatcher\Event;

class CustomerEvent extends Event
{
    /**
     * @var CustomerInterface
     */
    private $customer;

    public function __construct(CustomerInterface $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
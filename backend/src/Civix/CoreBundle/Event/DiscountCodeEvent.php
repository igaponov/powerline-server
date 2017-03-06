<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class DiscountCodeEvent extends Event
{
    /**
     * @var DiscountCode
     */
    private $discountCode;
    /**
     * @var User
     */
    private $user;

    public function __construct(DiscountCode $discountCode, User $user)
    {
        $this->discountCode = $discountCode;
        $this->user = $user;
    }

    /**
     * @return DiscountCode
     */
    public function getDiscountCode(): DiscountCode
    {
        return $this->discountCode;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
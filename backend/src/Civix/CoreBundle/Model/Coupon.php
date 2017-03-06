<?php

namespace Civix\CoreBundle\Model;

use Civix\CoreBundle\Entity\DiscountCode;

class Coupon
{
    /**
     * @var mixed
     */
    private $discountCode;

    public function __construct($discountCode)
    {
        $this->discountCode = $discountCode;
    }

    /**
     * @return string|DiscountCode
     */
    public function getDiscountCode()
    {
        return $this->discountCode;
    }

    public function getCode(): string
    {
        if ($this->discountCode instanceof DiscountCode) {
            return $this->discountCode->getOriginalCode();
        } else {
            return (string)$this->discountCode;
        }
    }

    public function hasOwner(): bool
    {
        return $this->discountCode instanceof DiscountCode && $this->discountCode->getOwner();
    }
}
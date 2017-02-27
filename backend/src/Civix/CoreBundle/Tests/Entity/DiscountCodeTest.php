<?php

namespace Civix\CoreBundle\Tests\Entity;

use Civix\CoreBundle\Entity\DiscountCode;

class DiscountCodeTest extends \PHPUnit_Framework_TestCase
{
    public function testCodeGeneration()
    {
        $code = new DiscountCode();
        $this->assertRegExp('/^[A-Z0-9]{12}$/', $code->getCode());
    }
}
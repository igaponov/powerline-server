<?php

namespace Civix\CoreBundle\Tests\Subscripption;

use Civix\CoreBundle\Model\Subscription\PackageLimitState;

class PackageLimitStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group subscription
     * @dataProvider dataLimits
     * @param $currentValue
     * @param $limit
     * @param $addValue
     * @param $hasLimit
     * @param $isAllowed
     */
    public function testPackageLimitState($currentValue, $limit, $addValue, $hasLimit, $isAllowed)
    {
        $packageLimitObj = new PackageLimitState();
        $packageLimitObj->setCurrentValue($currentValue);
        $packageLimitObj->setLimitValue($limit);

        $this->assertEquals($hasLimit, $packageLimitObj->hasLimitation());
        $this->assertEquals($isAllowed, $packageLimitObj->isAllowedWith($addValue));
    }

    /**
     * @group subscription
     * @dataProvider dataLimitsAllowed
     * @param $currentValue
     * @param $limit
     * @param $hasLimit
     * @param $isAllowed
     */
    public function testPackageLimitStateAllowed($currentValue, $limit, $hasLimit, $isAllowed)
    {
        $packageLimitObj = new PackageLimitState();
        $packageLimitObj->setCurrentValue($currentValue);
        $packageLimitObj->setLimitValue($limit);

        $this->assertEquals($hasLimit, $packageLimitObj->hasLimitation());
        $this->assertEquals($isAllowed, $packageLimitObj->isAllowed());
    }

    public function dataLimits()
    {
        return [
            [0, 4, 0, true, true],
            [0, 4, 4, true, true],
            [0, 4, 5, true, false],
            [4, 0, 0, true, false],
            [4, 4, 0, true, true],
            [4, 4, 1, true, false],
            [5, 4, 0, true, false],
            [0, null, 0, false, true],
            [4, null, 0, false, true],
            [4, null, 100, false, true],
            [0, -1, 0, true, false],
            [4, -1, 0,  true, false],
            [4, -1, 100,  true, false],
        ];
    }

    public function dataLimitsAllowed()
    {
        return [
            [0, 4, true, true],
            [4, 0, true, false],
            [3, 4, true, true],
            [4, 4, true, false],
            [5, 4, true, false],
            [0, null, false, true],
            [4, null, false, true],
            [0, -1, true, false],
            [4, -1, true, false],
        ];
    }
}

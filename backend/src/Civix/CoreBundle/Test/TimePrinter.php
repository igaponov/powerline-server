<?php

namespace Civix\CoreBundle\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Runner_BaseTestRunner;

class TimePrinter extends \PHPUnit_TextUI_ResultPrinter
{
    /**
     * @param \PHPUnit_Framework_Test|TestCase $test
     * @param float $time
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time): void
    {
        if ($test->getStatus() === PHPUnit_Runner_BaseTestRunner::STATUS_PASSED) {
            echo ' ';
        }
        $formatted = sprintf('%.2f', $time);
        if ($time > 1 ) {
            $formatted = $this->formatWithColor('fg-red', $formatted);
        } elseif ($time < 0.3) {
            $formatted = $this->formatWithColor('fg-green', $formatted);
        } else {
            $formatted = $this->formatWithColor('fg-yellow', $formatted);
        }
        $array = explode('\\', get_class($test));
        $className = array_pop($array);
        $className = array_reduce(array_reverse($array), function ($result, $name) {
            return $name[0].'\\'.$result;
        }, $this->formatWithColor('fg-yellow', $className));
        printf("%s %s::%s\n",
            $formatted,
            $className,
            $this->formatWithColor('fg-cyan', $test->getName())
        );
    }
}
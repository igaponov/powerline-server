<?php

namespace Civix\CoreBundle\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Test;
use PHPUnit_Runner_BaseTestRunner;

class TimePrinter extends \PHPUnit_TextUI_ResultPrinter
{
    /**
     * @var float
     */
    protected $memory;

    public function startTest(PHPUnit_Framework_Test $test): void
    {
        $this->memory = $this->getMemoryUsage();
        parent::startTest($test);
    }

    /**
     * @param \PHPUnit_Framework_Test|TestCase $test
     * @param float $time
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time): void
    {
        if ($test->getStatus() === PHPUnit_Runner_BaseTestRunner::STATUS_PASSED) {
            echo ' ';
        }
        $array = explode('\\', get_class($test));
        $className = array_pop($array);
        $className = array_reduce(array_reverse($array), function ($result, $name) {
            return $name[0].'\\'.$result;
        }, $this->formatWithColor('fg-yellow', $className));
        printf("%ssec %s %s::%s\n",
            $this->getFormattedTime($time),
            $this->getFormattedMemory(),
            $className,
            $this->formatWithColor('fg-cyan', $test->getName())
        );
    }

    private function getFormattedTime($time): string
    {
        $formatted = sprintf('%.2f', $time);
        if ($time > 1 ) {
            $formatted = $this->formatWithColor('fg-red', $formatted);
        } elseif ($time < 0.3) {
            $formatted = $this->formatWithColor('fg-green', $formatted);
        } else {
            $formatted = $this->formatWithColor('fg-yellow', $formatted);
        }

        return $formatted;
    }

    private function getFormattedMemory(): string
    {
        $memory = $this->getMemoryUsage();
        $delta = $memory - $this->memory;
        $result = sprintf('%4.1fMB %% %3.1fMB', $memory, $delta);
        if ($delta > 4) {
            $formatted = $this->formatWithColor('fg-red', $result);
        } elseif ($delta < 2) {
            $formatted = $this->formatWithColor('fg-green', $result);
        } else {
            $formatted = $this->formatWithColor('fg-yellow', $result);
        }

        return $formatted;
    }

    private function getMemoryUsage()
    {
        return memory_get_usage(true) / 1048576;
    }
}
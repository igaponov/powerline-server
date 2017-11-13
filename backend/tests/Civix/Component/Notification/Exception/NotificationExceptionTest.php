<?php

namespace Tests\Civix\Component\Notification\Exception;

use Civix\Component\Notification\Exception\NotificationException;
use PHPUnit\Framework\TestCase;

class NotificationExceptionTest extends TestCase
{
    public function testConstruction()
    {
        $level = NotificationException::CODE_DEBUG;
        $previous = new \Exception();
        $e = new NotificationException('Test!', $previous, $level);
        $this->assertSame('Notification error: Test!', $e->getMessage());
        $this->assertSame($previous, $e->getPrevious());
        $this->assertSame($level, $e->getLevel());
    }
}
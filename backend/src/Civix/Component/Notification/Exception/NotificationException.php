<?php

namespace Civix\Component\Notification\Exception;

use Psr\Log\LogLevel;
use Throwable;

class NotificationException extends \RuntimeException
{
    const CODE_DEBUG = LogLevel::DEBUG;
    const CODE_CRITICAL = LogLevel::CRITICAL;

    /**
     * @var string
     */
    private $level;

    public function __construct(
        string $message = '',
        Throwable $previous,
        string  $level = self::CODE_CRITICAL
    ) {
        parent::__construct('Notification error: '.$message, 0, $previous);
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }
}
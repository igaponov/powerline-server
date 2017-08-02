<?php

namespace Civix\Component\Notification\Exception;

use Civix\Component\Notification\Model\AbstractEndpoint;
use Throwable;

class AWSNotificationException extends NotificationException
{
    /**
     * @var AbstractEndpoint
     */
    private $endpoint;

    public function __construct($message = '', Throwable $previous, AbstractEndpoint $endpoint, $level = self::CODE_CRITICAL)
    {
        $this->endpoint = $endpoint;
        parent::__construct($message, $previous, $level);
    }

    /**
     * @return AbstractEndpoint
     */
    public function getEndpoint(): AbstractEndpoint
    {
        return $this->endpoint;
    }
}
<?php

namespace Civix\Component\Notification\Exception;

use Civix\Component\Notification\Model\Device;
use Throwable;

class OneSignalNotificationException extends NotificationException
{
    /**
     * @var Device
     */
    private $device;

    public function __construct($message = '', Throwable $previous, Device $device, $level = self::CODE_CRITICAL)
    {
        $this->device = $device;
        parent::__construct($message, $previous, $level);
    }

    /**
     * @return Device
     */
    public function getDevice(): Device
    {
        return $this->device;
    }
}
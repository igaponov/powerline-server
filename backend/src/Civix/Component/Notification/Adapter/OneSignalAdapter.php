<?php

namespace Civix\Component\Notification\Adapter;

use Civix\Component\Notification\DataFactory\DataFactoryInterface;
use Civix\Component\Notification\Exception\NotificationException;
use Civix\Component\Notification\Exception\OneSignalNotificationException;
use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\PushMessage;
use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\Retriever\DeviceRetrieverInterface;
use OneSignal\Exception\OneSignalException;
use OneSignal\Notifications;

class OneSignalAdapter implements AdapterInterface
{
    /**
     * @var DeviceRetrieverInterface
     */
    private $retriever;
    /**
     * @var Notifications
     */
    private $notifications;
    /**
     * @var DataFactoryInterface
     */
    private $dataFactory;

    public function __construct(
        DeviceRetrieverInterface $retriever,
        Notifications $notifications,
        DataFactoryInterface $dataFactory
    ) {
        $this->retriever = $retriever;
        $this->notifications = $notifications;
        $this->dataFactory = $dataFactory;
    }

    public function send(PushMessage $message, HandlerInterface $callback): void
    {
        $devices = $this->retriever->retrieve($message->getRecipient());
        foreach ($devices as $device) {
            try {
                $callback(null, $this->sendToDevice($message, $device));
            } catch (NotificationException $e) {
                $callback($e);
            }
        }
    }

    private function sendToDevice(PushMessage $message, Device $device): array
    {
        $data = $this->dataFactory->createData($message, $device);
        try {
            $this->notifications->add($data);

            return $data;
        } catch (OneSignalException $e) {
            throw new OneSignalNotificationException($e->getMessage(), $e, $device);
        }
    }
}
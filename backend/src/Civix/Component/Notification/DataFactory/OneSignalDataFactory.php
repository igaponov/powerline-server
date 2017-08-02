<?php

namespace Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\Model\ModelInterface;
use Civix\Component\Notification\PushMessage;

class OneSignalDataFactory implements DataFactoryInterface
{
    use ActionButtonInfoTrait;

    /**
     * @param PushMessage $message
     * @param ModelInterface|Device $model
     * @return array
     */
    public function createData(PushMessage $message, ModelInterface $model): array
    {
        return $this->createDeviceData($message, $model);
    }

    private function createDeviceData(PushMessage $message, Device $device)
    {
        return [
            'include_player_ids' => [$device->getId()],
            'data' => [
                'type' => $message->getType(),
                'image' => $message->getImage(),
                'user' => [
                    'id' => $message->getRecipient()->getId(),
                    'username' => $message->getRecipient()->getUsername(),
                ],
            ],
            'contents' => [
                'en' => $message->getMessage(),
            ],
            'headings' => [
                'en' => $message->getTitle(),
            ],
            'isIos' => $device->isIos(),
            'isAndroid' => $device->isAndroid(),
            'large_icon' => $message->getImage(), // android
            'buttons' => $this->getActionButtonInfo($message->getType()), // android
            'ios_category' => $message->getType(),
            'ios_badgeCount' => $message->getBadge(),
            'ios_badgeType' => 'SetTo',
            'ios_sound' => 'default',
        ];
    }
}
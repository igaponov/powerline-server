<?php

namespace Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\Model\AndroidEndpoint;
use Civix\Component\Notification\Model\IOSEndpoint;
use Civix\Component\Notification\Model\ModelInterface;
use Civix\Component\Notification\PushMessage;

class AWSDataFactory implements DataFactoryInterface
{
    use ActionButtonInfoTrait;

    /**
     * @param PushMessage $message
     * @param ModelInterface|AbstractEndpoint $model
     * @return array
     */
    public function createData(PushMessage $message, ModelInterface $model): array
    {
        return $this->createEndpointData($message, $model);
    }

    private function createEndpointData(PushMessage $message, AbstractEndpoint $endpoint)
    {
        if ($endpoint instanceof IOSEndpoint) {
            return $this->createIOSEndpointData($message);
        }
        if ($endpoint instanceof AndroidEndpoint) {
            return $this->createAndroidEndpointData($message);
        }
        throw new \RuntimeException(sprintf('Endpoint %s is not supported.', get_class($endpoint)));
    }

    private function createIOSEndpointData(PushMessage $message)
    {
        $notId = uniqid('', true);
        $payload = json_encode(
            [
                'aps' => [
                    'alert' => [
                        'title' => $message->getTitle(),
                        'body' => $message->getMessage(),
                    ],
                    'entity' => $message->getData(),
                    'type' => $message->getType(),
                    'category' => $message->getType(),
                    'sound' => 'default',
                    'title' => $message->getTitle(),
                    'image' => $message->getImage(),
                    'badge' => $message->getBadge(),
                    'additionalData' => [
                        'badgeCount' => $message->getBadge(),
                        'notId' => $notId,
                    ],
                ],
                'notId' => $notId,
            ]
        );

        return array(
            'default' => $message->getMessage(),
            'APNS' => $payload,
            'APNS_SANDBOX' => $payload,
        );
    }

    private function createAndroidEndpointData(PushMessage $message)
    {
        return array('GCM' => json_encode(array('data' => array(
            'message' => $message->getMessage(),
            'type' => $message->getType(),
            'entity' => $message->getData(),
            'title' => $message->getTitle(),
            'image' => $message->getImage(),
            'actions' => $this->getActionButtonInfo($message->getType()),
            'badge' => $message->getBadge(),
            'additionalData' => [
                'badgeCount' => $message->getBadge(),
            ],
        ))));
    }
}
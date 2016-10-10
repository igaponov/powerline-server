<?php

namespace Civix\CoreBundle\Entity\Notification;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class IOSEndpoint extends AbstractEndpoint
{
    public function getPlatformMessage($title, $message, $type, $entityData, $image, $badge = null)
    {
        $notId = uniqid('', true);
        $payload = json_encode(
            [
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body' => $message,
                    ],
                    'entity' => $entityData,
                    'type' => $type,
                    'category' => $type,
                    'sound' => 'default',
                    'title' => $title,
                    'image' => $image,
                    'badge' => $badge,
                    'additionalData' => [
                        'badgeCount' => $badge,
                        'notId' => $notId,
                    ],
                ],
                'notId' => $notId,
            ]
        );

        return json_encode(array(
            'default' => $message,
            'APNS' => $payload,
            'APNS_SANDBOX' => $payload,
        ));
    }
}

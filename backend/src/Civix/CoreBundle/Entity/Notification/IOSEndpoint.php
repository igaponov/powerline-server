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
        $payload = json_encode(
            array(
                'aps' => array(
                    'alert' => $message,
                    'entity' => json_encode($entityData),
                    'type' => $type,
                    'category' => $type,
                    'sound' => 'default',
                    'title' => $title,
                    'image' => $image,
                    'badge' => $badge,
                )
            )
        );

        return json_encode(array(
            'default' => $message,
            'APNS' => $payload,
            'APNS_SANDBOX' => $payload,
        ));
    }
}

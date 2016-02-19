<?php

namespace Civix\CoreBundle\Entity\Notification;

use Civix\CoreBundle\Service\PushSender;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AndroidEndpoint extends AbstractEndpoint
{
    public function getPlatformMessage($title, $message, $type, $entityData, $image)
    {
        $data = array(
            'message' => $message,
            'type' => $type,
            'entity' => json_encode($entityData),
            'title' => $title,
            'image' => $image,
        );

        switch($type) {
            case PushSender::TYPE_PUSH_MICRO_PETITION:
                $data['actions'] = array(
                    array("icon" => "Upvote", "title" => "Upvote", "callback" => "app.upvote"),
                    array("icon" => "Downvote", "title" => "Downvote", "callback" => "app.downvote")
                );
                break;

            case PushSender::TYPE_PUSH_INVITE:
                $data['actions'] = array(
                    array("icon" => "Join", "title" => "Join", "callback" => "app.join"),
                    array("icon" => "Ignore", "title" => "Ignore", "callback" => "app.ignore")
                );
                break;

            case PushSender::TYPE_PUSH_INFLUENCE:
                $data['actions'] = array(
                    array("icon" => "Approve", "title" => "Approve", "callback" => "app.approve"),
                    array("icon" => "Ignore", "title" => "Ignore", "callback" => "app.ignore")
                );
                break;

            case PushSender::TYPE_PUSH_SOCIAL_ACTIVITY:
                $data['actions'] = array(
                    array("icon" => "Upvote", "title" => "Upvote", "callback" => "app.upvote"),
                    array("icon" => "Open", "title" => "Open", "callback" => "app.open")
                );
                break;
        }

        return json_encode(array(
            'GCM' => json_encode(array('data' => $data))
        ));
    }
}

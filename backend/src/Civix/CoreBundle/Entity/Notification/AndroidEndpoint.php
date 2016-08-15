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
        return json_encode(array('GCM' => json_encode(array('data' => array(
            'message' => $message,
            'type' => $type,
            'entity' => json_encode($entityData),
            'title' => $title,
            'image' => $image,
            'actions' => $this->getActionButtonInfo($type),
        )))));
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param $type
     * @return array
     */
    private function getActionButtonInfo($type)
    {
        $actionButton = array();

        switch ($type) {
            case PushSender::TYPE_PUSH_USER_PETITION:
                $actionButton = array(
                    array("icon" => "Upvote", "title" => "Upvote", "callback" => "app.upvote"),
                    array("icon" => "Downvote", "title" => "Downvote", "callback" => "app.downvote")
                );
                break;

            case PushSender::TYPE_PUSH_INVITE:
                $actionButton = array(
                    array("icon" => "Join", "title" => "Join", "callback" => "app.join"),
                    array("icon" => "Ignore", "title" => "Ignore", "callback" => "app.ignore")
                );
                break;

            case PushSender::TYPE_PUSH_INFLUENCE:
                $actionButton = array(
                    array("icon" => "Approve", "title" => "Approve", "callback" => "app.approve"),
                    array("icon" => "Ignore", "title" => "Ignore", "callback" => "app.ignore")
                );
                break;

            case PushSender::TYPE_PUSH_SOCIAL_ACTIVITY:
                $actionButton = array(
                    array("icon" => "Upvote", "title" => "Upvote", "callback" => "app.upvote"),
                    array("icon" => "Open", "title" => "Open", "callback" => "app.open")
                );
                break;
        }

        return $actionButton;
    }
}

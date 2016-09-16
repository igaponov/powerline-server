<?php

namespace Civix\CoreBundle\Entity\Notification;

use Civix\CoreBundle\Entity\SocialActivity;
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
                    array("icon" => "Sign", "title" => "Sign", "callback" => "app.sign"),
                );
                break;
            case PushSender::TYPE_PUSH_INFLUENCE:
                $actionButton = array(
                    array("icon" => "Approve", "title" => "Approve", "callback" => "app.approve"),
                );
                break;
            case SocialActivity::TYPE_COMMENT_MENTIONED:
                $actionButton = array(
                    array("icon" => "Open", "title" => "Open", "callback" => "app.open"),
                );
                break;
            case SocialActivity::TYPE_FOLLOW_REQUEST:
                $actionButton = array(
                    array("icon" => "Approve", "title" => "Approve", "callback" => "app.approve"),
                );
                break;
            case SocialActivity::TYPE_GROUP_POST_CREATED:
                $actionButton = array(
                    array("icon" => "Upvote", "title" => "Upvote", "callback" => "app.upvote"),
                );
                break;
            case SocialActivity::TYPE_GROUP_USER_PETITION_CREATED:
                $actionButton = array(
                    array("icon" => "Sign", "title" => "Sign", "callback" => "app.sign"),
                );
                break;
            case PushSender::TYPE_PUSH_POST:
                $actionButton = array(
                    array("icon" => "Upvote", "title" => "Upvote", "callback" => "app.upvote"),
                    array("icon" => "Downvote", "title" => "Downvote", "callback" => "app.downvote"),
                );
                break;
            case PushSender::TYPE_PUSH_INVITE:
                $actionButton = array(
                    array("icon" => "Join", "title" => "Join", "callback" => "app.join"),
                );
                break;
            case SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED:
                $actionButton = array(
                    array("icon" => "Open", "title" => "Open", "callback" => "app.open"),
                );
                break;
            case SocialActivity::TYPE_OWN_POST_COMMENTED:
            case SocialActivity::TYPE_OWN_POST_VOTED:
            case SocialActivity::TYPE_FOLLOW_POST_COMMENTED:
            case SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED:
            case SocialActivity::TYPE_OWN_USER_PETITION_SIGNED:
            case SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED:
                $actionButton = array(
                    array("icon" => "View", "title" => "View", "callback" => "app.view"),
                    array("icon" => "Mute", "title" => "Mute", "callback" => "app.mute"),
                );
                break;
            case SocialActivity::TYPE_COMMENT_REPLIED:
                $actionButton = array(
                    array("icon" => "Reply", "title" => "Reply", "callback" => "app.reply"),
                );
                break;
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $actionButton = array(
                    array("icon" => "Share", "title" => "Share", "callback" => "app.share"),
                );
                break;
            case 'group_petition':
                $actionButton = array(
                    array("icon" => "Sign", "title" => "Sign", "callback" => "app.sign"),
                    array("icon" => "View", "title" => "View", "callback" => "app.view"),
                );
                break;
            case 'group_question':
                $actionButton = array(
                    array("icon" => "Respond", "title" => "Respond", "callback" => "app.respond"),
                );
                break;
            case 'group_news':
                $actionButton = array(
                    array("icon" => "Open", "title" => "Open", "callback" => "app.open"),
                );
                break;
            case 'group_event':
                $actionButton = array(
                    array("icon" => "RSVP", "title" => "RSVP", "callback" => "app.rsvp"),
                );
                break;
            case 'group_payment_request':
            case 'group_payment_request_crowdfunding':
                $actionButton = array(
                    array("icon" => "Donate", "title" => "Donate", "callback" => "app.donate"),
                );
                break;
        }

        return $actionButton;
    }
}

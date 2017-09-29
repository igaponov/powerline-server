<?php

namespace Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\Model\ModelInterface;
use Civix\Component\Notification\PushMessage;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\PushSender;

class OneSignalDataFactory implements DataFactoryInterface
{
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
            'ios_badgeCount' => $message->getBadge(),
            'ios_badgeType' => 'SetTo',
            'ios_sound' => 'default',
        ];
    }

    /**
     * @param $type
     * @return array
     */
    private function getActionButtonInfo($type): array
    {
        $actionButton = [];
        switch ($type) {
            case PushSender::TYPE_PUSH_USER_PETITION_BOOSTED:
                $actionButton = [
                    ['id' => 'sign-button', 'text' => 'Sign', 'icon' => 'ic_civix_sign'],
                ];
                break;
            case PushSender::TYPE_PUSH_OWN_USER_PETITION_BOOSTED:
                $actionButton = [
                    ['id' => 'view-button', 'text' => 'View', 'icon' => 'ic_civix_view'],
                ];
                break;
            case SocialActivity::TYPE_COMMENT_MENTIONED:
            case SocialActivity::TYPE_POST_MENTIONED:
                $actionButton = [
                    ['id' => 'open-button', 'text' => 'Open', 'icon' => 'ic_civix_open'],
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_REQUEST:
                $actionButton = [
                    ['id' => 'approve-button', 'text' => 'Approve', 'icon' => 'ic_civix_approve'],
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_POST_CREATED:
                $actionButton = [
                    ['id' => 'upvote-button', 'text' => 'Upvote', 'icon' => 'ic_civix_upvote'],
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED:
                $actionButton = [
                    ['id' => 'sign-button', 'text' => 'Sign', 'icon' => 'ic_civix_sign'],
                ];
                break;
            case PushSender::TYPE_PUSH_POST_BOOSTED:
                $actionButton = [
                    ['id' => 'upvote-button', 'text' => 'Upvote', 'icon' => 'ic_civix_upvote'],
                    ['id' => 'downvote-button', 'text' => 'Downvote', 'icon' => 'ic_civix_downvote'],
                ];
                break;
            case PushSender::TYPE_PUSH_OWN_POST_BOOSTED:
                $actionButton = [
                    ['id' => 'view-button', 'text' => 'View', 'icon' => 'ic_civix_view'],
                ];
                break;
            case PushSender::TYPE_PUSH_INVITE:
                $actionButton = [
                    ['id' => 'join-button', 'text' => 'Join', 'icon' => 'ic_civix_join'],
                ];
                break;
            case SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED:
                $actionButton = [
                    ['id' => 'open-button', 'text' => 'Open', 'icon' => 'ic_civix_open'],
                ];
                break;
            case SocialActivity::TYPE_OWN_POLL_COMMENTED:
            case SocialActivity::TYPE_OWN_POST_COMMENTED:
            case SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED:
            case SocialActivity::TYPE_OWN_POLL_ANSWERED:
            case SocialActivity::TYPE_OWN_POST_VOTED:
            case SocialActivity::TYPE_OWN_USER_PETITION_SIGNED:
            case SocialActivity::TYPE_FOLLOW_POLL_COMMENTED:
            case SocialActivity::TYPE_FOLLOW_POST_COMMENTED:
            case SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED:
                $actionButton = [
                    ['id' => 'view-button', 'text' => 'View', 'icon' => 'ic_civix_view'],
                    ['id' => 'mute-button', 'text' => 'Mute', 'icon' => 'ic_civix_mute'],
                ];
                break;
            case SocialActivity::TYPE_COMMENT_REPLIED:
                $actionButton = [
                    ['id' => 'reply-button', 'text' => 'Reply', 'icon' => 'ic_civix_reply'],
                ];
                break;
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $actionButton = [
                    ['id' => 'share-button', 'text' => 'Share', 'icon' => 'ic_civix_share'],
                ];
                break;
            case 'group_petition':
                $actionButton = [
                    ['id' => 'sign-button', 'text' => 'Sign', 'icon' => 'ic_civix_sign'],
                    ['id' => 'view-button', 'text' => 'View', 'icon' => 'ic_civix_view'],
                ];
                break;
            case 'group_question':
                $actionButton = [
                    ['id' => 'respond-button', 'text' => 'Respond', 'icon' => 'ic_civix_respond'],
                ];
                break;
            case 'group_news':
                $actionButton = [
                    ['id' => 'open-button', 'text' => 'Open', 'icon' => 'ic_civix_open'],
                ];
                break;
            case 'group_event':
                $actionButton = [
                    ['id' => 'rsvp-button', 'text' => 'RSVP', 'icon' => 'ic_civix_rsvp'],
                ];
                break;
            case 'group_payment_request':
            case 'group_payment_request_crowdfunding':
                $actionButton = [
                    ['id' => 'donate-button', 'text' => 'Donate', 'icon' => 'ic_civix_donate'],
                ];
                break;
        }

        return $actionButton;
    }
}
<?php

namespace Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\Model\ModelInterface;
use Civix\Component\Notification\PushMessage;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\PushSender;

class OneSignalDataFactory implements DataFactoryInterface
{
    private const BUTTON_IGNORE = ['id' => 'ignore-button', 'text' => 'Ignore', 'icon' => 'ic_civix_ignore'];

    private const BUTTON_APPROVE_FOLLOW_REQUEST = [
        'id' => 'approve-follow-request-button',
        'text' => 'Approve',
        'icon' => 'ic_civix_approve'
    ];
    private const BUTTON_IGNORE_FOLLOW_REQUEST = [
        'id' => 'ignore-follow-request-button',
        'text' => 'Ignore',
        'icon' => 'ic_civix_ignore'
    ];

    private const BUTTON_OPEN_GROUP = ['id' => 'open-group-button', 'text' => 'Open', 'icon' => 'ic_civix_open'];
    private const BUTTON_JOIN_GROUP = ['id' => 'join-group-button', 'text' => 'Join', 'icon' => 'ic_civix_join'];
    private const BUTTON_IGNORE_INVITE = [
        'id' => 'ignore-invite-button',
        'text' => 'Ignore',
        'icon' => 'ic_civix_ignore'
    ];

    private const BUTTON_OPEN_COMMENT = ['id' => 'open-comment-button', 'text' => 'Open', 'icon' => 'ic_civix_open'];
    private const BUTTON_REPLY_COMMENT = ['id' => 'reply-comment-button', 'text' => 'Reply', 'icon' => 'ic_civix_reply'];

    private const BUTTON_SHARE_ANNOUNCEMENT = [
        'id' => 'share-announcement-button',
        'text' => 'Share',
        'icon' => 'ic_civix_share'
    ];

    private const BUTTON_SIGN_PETITION = ['id' => 'sign-petition-button', 'text' => 'Sign', 'icon' => 'ic_civix_sign'];
    private const BUTTON_VIEW_PETITION = ['id' => 'view-petition-button', 'text' => 'View', 'icon' => 'ic_civix_view'];
    private const BUTTON_MUTE_PETITION = ['id' => 'mute-petition-button', 'text' => 'Mute', 'icon' => 'ic_civix_mute'];

    private const BUTTON_UPVOTE_POST = ['id' => 'upvote-post-button', 'text' => 'Upvote', 'icon' => 'ic_civix_upvote'];
    private const BUTTON_DOWNVOTE_POST = ['id' => 'downvote-post-button', 'text' => 'Downvote', 'icon' => 'ic_civix_downvote'];
    private const BUTTON_OPEN_POST = ['id' => 'open-post-button', 'text' => 'Open', 'icon' => 'ic_civix_open'];
    private const BUTTON_VIEW_POST = ['id' => 'view-post-button', 'text' => 'View', 'icon' => 'ic_civix_view'];
    private const BUTTON_MUTE_POST = ['id' => 'mute-post-button', 'text' => 'Mute', 'icon' => 'ic_civix_mute'];
    private const BUTTON_SHARE_POST = ['id' => 'share-post-button', 'text' => 'Share', 'icon' => 'ic_civix_share'];

    private const BUTTON_VIEW_POLL = ['id' => 'view-poll-button', 'text' => 'View', 'icon' => 'ic_civix_view'];
    private const BUTTON_MUTE_POLL = ['id' => 'mute-poll-button', 'text' => 'Mute', 'icon' => 'ic_civix_mute'];
    private const BUTTON_RESPOND_POLL = ['id' => 'respond-button', 'text' => 'Respond', 'icon' => 'ic_civix_respond'];
    private const BUTTON_OPEN_POLL = ['id' => 'open-poll-button', 'text' => 'Open', 'icon' => 'ic_civix_open'];
    private const BUTTON_RSVP = ['id' => 'rsvp-button', 'text' => 'RSVP', 'icon' => 'ic_civix_rsvp'];
    private const BUTTON_DONATE = ['id' => 'donate-button', 'text' => 'Donate', 'icon' => 'ic_civix_donate'];
    private const BUTTON_SIGN_LEADER_PETITION = ['id' => 'sign-leader-petition-button', 'text' => 'Sign', 'icon' => 'ic_civix_sign'];
    private const BUTTON_VIEW_LEADER_PETITION = ['id' => 'view-leader-petition-button', 'text' => 'View', 'icon' => 'ic_civix_view'];

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
                'entity' => $message->getData(),
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
            'ios_attachments' => ['large_icon' => $message->getImage()],
            'ios_category' => $message->getType(),
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
                    self::BUTTON_SIGN_PETITION,
                    self::BUTTON_IGNORE,
                ];
                break;
            case PushSender::TYPE_PUSH_OWN_USER_PETITION_BOOSTED:
                $actionButton = [
                    self::BUTTON_VIEW_PETITION,
                    self::BUTTON_IGNORE,
                ];
                break;
            case SocialActivity::TYPE_COMMENT_MENTIONED:
                $actionButton = [
                    self::BUTTON_OPEN_COMMENT,
                    self::BUTTON_IGNORE,
                ];
                break;
            case SocialActivity::TYPE_POST_MENTIONED:
                $actionButton = [
                    self::BUTTON_OPEN_POST,
                    self::BUTTON_IGNORE,
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_REQUEST:
                $actionButton = [
                    self::BUTTON_APPROVE_FOLLOW_REQUEST,
                    self::BUTTON_IGNORE_FOLLOW_REQUEST,
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_POST_CREATED:
            case PushSender::TYPE_PUSH_POST_BOOSTED:
                $actionButton = [
                    self::BUTTON_UPVOTE_POST,
                    self::BUTTON_DOWNVOTE_POST,
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED:
                $actionButton = [
                    self::BUTTON_SIGN_PETITION,
                    self::BUTTON_IGNORE,
                ];
                break;
            case PushSender::TYPE_PUSH_OWN_POST_BOOSTED:
                $actionButton = [
                    self::BUTTON_VIEW_POST,
                    self::BUTTON_IGNORE,
                ];
                break;
            case PushSender::TYPE_PUSH_INVITE:
                $actionButton = [
                    self::BUTTON_JOIN_GROUP,
                    self::BUTTON_IGNORE_INVITE,
                ];
                break;
            case SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED:
                $actionButton = [
                    self::BUTTON_OPEN_GROUP,
                    self::BUTTON_IGNORE,
                ];
                break;
            case SocialActivity::TYPE_OWN_POLL_COMMENTED:
            case SocialActivity::TYPE_OWN_POLL_ANSWERED:
            case SocialActivity::TYPE_FOLLOW_POLL_COMMENTED:
                $actionButton = [
                    self::BUTTON_VIEW_POLL,
                    self::BUTTON_MUTE_POLL,
                ];
                break;
            case SocialActivity::TYPE_OWN_POST_COMMENTED:
            case SocialActivity::TYPE_OWN_POST_VOTED:
            case SocialActivity::TYPE_FOLLOW_POST_COMMENTED:
                $actionButton = [
                    self::BUTTON_VIEW_POST,
                    self::BUTTON_MUTE_POST,
                ];
                break;
            case SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED:
            case SocialActivity::TYPE_OWN_USER_PETITION_SIGNED:
            case SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED:
                $actionButton = [
                    self::BUTTON_VIEW_PETITION,
                    self::BUTTON_MUTE_PETITION,
                ];
                break;
            case SocialActivity::TYPE_COMMENT_REPLIED:
                $actionButton = [
                    self::BUTTON_REPLY_COMMENT,
                    self::BUTTON_IGNORE,
                ];
                break;
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $actionButton = [
                    self::BUTTON_SHARE_ANNOUNCEMENT,
                    self::BUTTON_IGNORE,
                ];
                break;
            case PushSender::TYPE_PUSH_POST_SHARED:
                $actionButton = [
                    self::BUTTON_UPVOTE_POST,
                    self::BUTTON_SHARE_POST,
                ];
                break;
            case 'group_petition':
                $actionButton = [
                    self::BUTTON_SIGN_LEADER_PETITION,
                    self::BUTTON_VIEW_LEADER_PETITION,
                ];
                break;
            case 'group_question':
                $actionButton = [
                    self::BUTTON_RESPOND_POLL,
                    self::BUTTON_IGNORE,
                ];
                break;
            case 'group_news':
                $actionButton = [
                    self::BUTTON_OPEN_POLL,
                    self::BUTTON_IGNORE,
                ];
                break;
            case 'group_event':
                $actionButton = [
                    self::BUTTON_RSVP,
                    self::BUTTON_IGNORE,
                ];
                break;
            case 'group_payment_request':
            case 'group_payment_request_crowdfunding':
                $actionButton = [
                    self::BUTTON_DONATE,
                    self::BUTTON_IGNORE,
                ];
                break;
        }

        return $actionButton;
    }
}
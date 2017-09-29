<?php

namespace Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\Model\AndroidEndpoint;
use Civix\Component\Notification\Model\IOSEndpoint;
use Civix\Component\Notification\Model\ModelInterface;
use Civix\Component\Notification\PushMessage;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\PushSender;

class AWSDataFactory implements DataFactoryInterface
{
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
                    ['icon' => 'Sign', 'title' => 'Sign', 'callback' => 'app.sign'],
                ];
                break;
            case PushSender::TYPE_PUSH_OWN_USER_PETITION_BOOSTED:
                $actionButton = [
                    ['icon' => 'View', 'title' => 'View', 'callback' => 'app.view'],
                ];
                break;
            case SocialActivity::TYPE_COMMENT_MENTIONED:
            case SocialActivity::TYPE_POST_MENTIONED:
                $actionButton = [
                    ['icon' => 'Open', 'title' => 'Open', 'callback' => 'app.open'],
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_REQUEST:
                $actionButton = [
                    ['icon' => 'Approve', 'title' => 'Approve', 'callback' => 'app.approve'],
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_POST_CREATED:
                $actionButton = [
                    ['icon' => 'Upvote', 'title' => 'Upvote', 'callback' => 'app.upvote'],
                ];
                break;
            case SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED:
                $actionButton = [
                    ['icon' => 'Sign', 'title' => 'Sign', 'callback' => 'app.sign'],
                ];
                break;
            case PushSender::TYPE_PUSH_POST_BOOSTED:
                $actionButton = [
                    ['icon' => 'Upvote', 'title' => 'Upvote', 'callback' => 'app.upvote'],
                    ['icon' => 'Downvote', 'title' => 'Downvote', 'callback' => 'app.downvote'],
                ];
                break;
            case PushSender::TYPE_PUSH_OWN_POST_BOOSTED:
                $actionButton = [
                    ['icon' => 'View', 'title' => 'View', 'callback' => 'app.view'],
                ];
                break;
            case PushSender::TYPE_PUSH_INVITE:
                $actionButton = [
                    ['icon' => 'Join', 'title' => 'Join', 'callback' => 'app.join'],
                ];
                break;
            case SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED:
                $actionButton = [
                    ['icon' => 'Open', 'title' => 'Open', 'callback' => 'app.open'],
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
                    ['icon' => 'View', 'title' => 'View', 'callback' => 'app.view'],
                    ['icon' => 'Mute', 'title' => 'Mute', 'callback' => 'app.mute'],
                ];
                break;
            case SocialActivity::TYPE_COMMENT_REPLIED:
                $actionButton = [
                    ['icon' => 'Reply', 'title' => 'Reply', 'callback' => 'app.reply'],
                ];
                break;
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $actionButton = [
                    ['icon' => 'Share', 'title' => 'Share', 'callback' => 'app.share'],
                ];
                break;
            case 'group_petition':
                $actionButton = [
                    ['icon' => 'Sign', 'title' => 'Sign', 'callback' => 'app.sign'],
                    ['icon' => 'View', 'title' => 'View', 'callback' => 'app.view'],
                ];
                break;
            case 'group_question':
                $actionButton = [
                    ['icon' => 'Respond', 'title' => 'Respond', 'callback' => 'app.respond'],
                ];
                break;
            case 'group_news':
                $actionButton = [
                    ['icon' => 'Open', 'title' => 'Open', 'callback' => 'app.open'],
                ];
                break;
            case 'group_event':
                $actionButton = [
                    ['icon' => 'RSVP', 'title' => 'RSVP', 'callback' => 'app.rsvp'],
                ];
                break;
            case 'group_payment_request':
            case 'group_payment_request_crowdfunding':
                $actionButton = [
                    ['icon' => 'Donate', 'title' => 'Donate', 'callback' => 'app.donate'],
                ];
                break;
        }

        return $actionButton;
    }
}
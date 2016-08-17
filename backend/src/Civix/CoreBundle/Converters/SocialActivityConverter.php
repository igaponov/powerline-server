<?php

namespace Civix\CoreBundle\Converters;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\Micropetitions\Petition as Micropetition;

class SocialActivityConverter
{
    private static $Converters = [
        SocialActivity::TYPE_FOLLOW_REQUEST => 'getFollowRequest',
        SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED => 'getJoinToGroupApproved',
        SocialActivity::TYPE_GROUP_POST_CREATED => 'getMicropetitionCreated',
        SocialActivity::TYPE_ANSWERED => 'getAnswered',
        SocialActivity::TYPE_FOLLOW_POLL_COMMENTED => 'getFollowPollCommented',
        SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED => 'getFollowMicropetitionCommented',
        SocialActivity::TYPE_COMMENT_REPLIED => 'getCommentReplied',
        SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED => 'getGroupPermissionsChanged',
        SocialActivity::TYPE_COMMENT_MENTIONED => 'getCommentMentioned',
        SocialActivity::TYPE_OWN_POST_COMMENTED => 'getOwnPostCommented',
        SocialActivity::TYPE_OWN_POST_VOTED => 'getOwnPostVoted',
    ];

    public static function toHTML(SocialActivity $entity)
    {
        if (isset(self::$Converters[$entity->getType()])) {
            $method = self::$Converters[$entity->getType()].'HTML';

            return self::$method($entity);
        }
    }

    public static function toText(SocialActivity $entity)
    {
        if (isset(self::$Converters[$entity->getType()])) {
            $method = self::$Converters[$entity->getType()].'Text';

            return self::$method($entity);
        }
    }

    public static function toTitle(SocialActivity $entity)
    {
        if (isset(self::$Converters[$entity->getType()])) {
            $method = self::$Converters[$entity->getType()] . 'Title';
            return self::$method($entity);
        }
    }

    public static function toImage(SocialActivity $entity)
    {
        if (isset(self::$Converters[$entity->getType()])) {
            $method = self::$Converters[$entity->getType()] . 'Image';
            return self::$method($entity);
        }
    }

    private static function getFollowRequestHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> wants to follow you</p>';
    }

    private static function getFollowRequestText(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName() . ' wants to follow you. Approve?';
    }

    private static function getFollowRequestTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getFollowRequestImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getJoinToGroupApprovedHTML(SocialActivity $entity)
    {
        return '<p>Request to join <strong>'.htmlspecialchars($entity->getGroup()->getOfficialName())
            .'</strong> has been approved</p>';
    }

    private static function getJoinToGroupApprovedText(SocialActivity $entity)
    {
        return 'Request to join '.$entity->getGroup()->getOfficialName().' has been approved';
    }

    private static function getJoinToGroupApprovedTitle(SocialActivity $entity)
    {
        return $entity->getGroup()->getOfficialName();
    }

    private static function getJoinToGroupApprovedImage(SocialActivity $entity)
    {
        return $entity->getGroup()->getAvatarFileName();
    }

    private static function getMicropetitionCreatedHTML(SocialActivity $entity)
    {
        if ($entity->getTarget()['type'] === Micropetition::TYPE_QUORUM) {
            return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName())
                .'</strong> posted in the <strong>'
                .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
        }
        if ($entity->getTarget()['type'] === Micropetition::TYPE_LONG_PETITION) {
            return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName())
                .'</strong> created a petition in the <strong>'
                .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
        }
    }

    private static function getMicropetitionCreatedText(SocialActivity $entity)
    {
        if ($entity->getTarget()['type'] === Micropetition::TYPE_QUORUM) {
            return 'Posted: '.$entity->getTarget()['body'];
        }
        if ($entity->getTarget()['type'] === Micropetition::TYPE_LONG_PETITION) {
            return 'Posted: ' . $entity->getTarget()['title'];
        }
    }

    private static function getMicropetitionCreatedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getMicropetitionCreatedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getAnsweredHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> responded to a '
            .$entity->getTarget()['label'].' "'.htmlspecialchars($entity->getTarget()['preview'])
            .'" in the <strong>'.htmlspecialchars($entity->getGroup()->getOfficialName())
            .'</strong> community</p>';
    }

    private static function getAnsweredText(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName().' responded to a '
            .$entity->getTarget()['label'].' "'.$entity->getTarget()['preview']
            .'" in the '.$entity->getGroup()->getOfficialName().' community';
    }

    private static function getAnsweredTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getAnsweredImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getFollowPollCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> commented on '
            .$entity->getTarget()['label'].' in the <strong>'
            .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
    }

    private static function getFollowPollCommentedText(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName().' commented on '
            .$entity->getTarget()['label'].' in the '.$entity->getGroup()->getOfficialName().' community';
    }

    private static function getFollowPollCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getFollowPollCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getFollowMicropetitionCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> commented on '
            .$entity->getTarget()['label'].' in the <strong>'
            .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
    }

    private static function getFollowMicropetitionCommentedText(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName().' commented on '.$entity->getTarget()['label']
            .' in the <strong>'.$entity->getGroup()->getOfficialName().' community';
    }

    private static function getFollowMicropetitionCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getFollowMicropetitionCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getCommentRepliedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName())
            .'</strong> replied to your comment</p>';
    }

    private static function getCommentRepliedText(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName().' replied to your comment';
    }

    private static function getCommentRepliedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getCommentRepliedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getGroupPermissionsChangedHTML(SocialActivity $entity)
    {
        return '<p>Permissions changed for <strong>'.htmlspecialchars($entity->getGroup()->getOfficialName())
            .'</strong></p>';
    }

    private static function getGroupPermissionsChangedText(SocialActivity $entity)
    {
        return $entity->getGroup()->getOfficialName().' has changed the information it is asking for from you as a group member. Open to learn more.';
    }

    private static function getGroupPermissionsChangedTitle(SocialActivity $entity)
    {
        return 'Group Permissions Changed';
    }

    private static function getGroupPermissionsChangedImage(SocialActivity $entity)
    {
        return $entity->getGroup()->getAvatarFileName();
    }

    private static function getCommentMentionedText(SocialActivity $entity)
    {
        return $entity->getTarget()['first_name'].' mentioned you in a comment';
    }

    private static function getCommentMentionedTitle(SocialActivity $entity)
    {
        return $entity->getTarget()['first_name'].' '.$entity->getTarget()['last_name'];
    }

    private static function getCommentMentionedImage(SocialActivity $entity)
    {
        return $entity->getTarget()['image'];
    }

    private static function getCommentMentionedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getTarget()['first_name'])
            .'</strong> mentioned you in a comment</p>';
    }

    private static function getOwnPostCommentedText(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName().' commented on your post';
    }

    private static function getOwnPostCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getOwnPostCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getOwnPostCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'. htmlspecialchars($entity->getFollowing()->getFullName())
        . '</strong> commented on your post</p>';
    }

    private static function getOwnPostVotedText(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName().' voted on your post';
    }

    private static function getOwnPostVotedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getOwnPostVotedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getOwnPostVotedHTML(SocialActivity $entity)
    {
        return '<p><strong>'. htmlspecialchars($entity->getFollowing()->getFullName())
        . '</strong> voted on your post</p>';
    }
}
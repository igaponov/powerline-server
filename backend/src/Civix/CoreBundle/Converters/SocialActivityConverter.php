<?php

namespace Civix\CoreBundle\Converters;

use Civix\CoreBundle\Entity\SocialActivity;

class SocialActivityConverter
{
    private static $Converters = [
        SocialActivity::TYPE_FOLLOW_REQUEST => 'getFollowRequest',

        SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED => 'getJoinToGroupApproved',

        SocialActivity::TYPE_GROUP_POST_CREATED => 'getPostCreated',
        SocialActivity::TYPE_GROUP_USER_PETITION_CREATED => 'getUserPetitionCreated',
        SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED => 'getGroupPermissionsChanged',

        SocialActivity::TYPE_COMMENT_REPLIED => 'getCommentReplied',
        SocialActivity::TYPE_COMMENT_MENTIONED => 'getCommentMentioned',

        SocialActivity::TYPE_FOLLOW_POLL_COMMENTED => 'getFollowPollCommented',
        SocialActivity::TYPE_FOLLOW_POST_COMMENTED => 'getFollowPostCommented',
        SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED => 'getFollowUserPetitionCommented',

        SocialActivity::TYPE_OWN_POLL_COMMENTED => 'getOwnPollCommented',
        SocialActivity::TYPE_OWN_POST_COMMENTED => 'getOwnPostCommented',
        SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED => 'getOwnUserPetitionCommented',

        SocialActivity::TYPE_OWN_POLL_ANSWERED => 'getOwnPollAnswered',
        SocialActivity::TYPE_OWN_POST_VOTED => 'getOwnPostVoted',
        SocialActivity::TYPE_OWN_USER_PETITION_SIGNED => 'getOwnUserPetitionSigned',
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
        return ' wants to follow you. Approve?';
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

    private static function getPostCreatedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName())
            .'</strong> posted in the <strong>'
            .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
    }

    private static function getPostCreatedText(SocialActivity $entity)
    {
        return 'posted: '.self::preview($entity->getTarget()['body']);
    }

    private static function getPostCreatedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getPostCreatedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getUserPetitionCreatedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName())
            .'</strong> created a petition in the <strong>'
            .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
    }

    private static function getUserPetitionCreatedText(SocialActivity $entity)
    {
        return self::preview($entity->getTarget()['body']);
    }

    private static function getUserPetitionCreatedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getUserPetitionCreatedImage(SocialActivity $entity)
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

    private static function getCommentRepliedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName())
        .'</strong> replied to your comment</p>';
    }

    private static function getCommentRepliedText(SocialActivity $entity)
    {
        return self::preview(' replied and said '.$entity->getTarget()['preview']);
    }

    private static function getCommentRepliedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getCommentRepliedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getCommentMentionedText(SocialActivity $entity)
    {
        return ' mentioned you in a comment';
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

    private static function getFollowPollCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> commented on '
        .$entity->getTarget()['label'].' in the <strong>'
        .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
    }

    private static function getFollowPollCommentedText(SocialActivity $entity)
    {
        return ' commented on your poll';
    }

    private static function getFollowPollCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getFollowPollCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getFollowPostCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> commented on the post you subscribed to</p>';
    }

    private static function getFollowPostCommentedText(SocialActivity $entity)
    {
        return ' commented on the post you subscribed to';
    }

    private static function getFollowPostCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getFollowPostCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getFollowUserPetitionCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> commented on '
        .$entity->getTarget()['label'].' in the <strong>'
        .htmlspecialchars($entity->getGroup()->getOfficialName()).'</strong> community</p>';
    }

    private static function getFollowUserPetitionCommentedText(SocialActivity $entity)
    {
        return ' commented on the petition you subscribed to';
    }

    private static function getFollowUserPetitionCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getFollowUserPetitionCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getOwnPollCommentedText(SocialActivity $entity)
    {
        return ' commented on your poll';
    }

    private static function getOwnPollCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getOwnPollCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getOwnPollCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'. htmlspecialchars($entity->getFollowing()->getFullName())
        . '</strong> commented on your poll</p>';
    }

    private static function getOwnPostCommentedText(SocialActivity $entity)
    {
        return ' commented on your post';
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

    private static function getOwnUserPetitionCommentedText(SocialActivity $entity)
    {
        return ' commented on your petition';
    }

    private static function getOwnUserPetitionCommentedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getOwnUserPetitionCommentedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getOwnUserPetitionCommentedHTML(SocialActivity $entity)
    {
        return '<p><strong>'. htmlspecialchars($entity->getFollowing()->getFullName())
        . '</strong> commented on your petition</p>';
    }

    private static function getOwnPollAnsweredHTML(SocialActivity $entity)
    {
        return '<p><strong>'.htmlspecialchars($entity->getFollowing()->getFullName()).'</strong> responded to a '
            .$entity->getTarget()['label'].' "'.htmlspecialchars($entity->getTarget()['preview'])
            .'" in the <strong>'.htmlspecialchars($entity->getGroup()->getOfficialName())
            .'</strong> community</p>';
    }

    private static function getOwnPollAnsweredText(SocialActivity $entity)
    {
        return ' responded to your poll';
    }

    private static function getOwnPollAnsweredTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getOwnPollAnsweredImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getOwnPostVotedText(SocialActivity $entity)
    {
        return ' voted on your post';
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

    private static function getOwnUserPetitionSignedText(SocialActivity $entity)
    {
        return ' signed your petition';
    }

    private static function getOwnUserPetitionSignedTitle(SocialActivity $entity)
    {
        return $entity->getFollowing()->getFullName();
    }

    private static function getOwnUserPetitionSignedImage(SocialActivity $entity)
    {
        return $entity->getFollowing()->getAvatarFileName();
    }

    private static function getOwnUserPetitionSignedHTML(SocialActivity $entity)
    {
        return '<p><strong>'. htmlspecialchars($entity->getFollowing()->getFullName())
        . '</strong> signed your petition</p>';
    }

    private static function preview($text)
    {
        if (mb_strlen($text) > 300) {
            return mb_substr($text, 0, 300) . '...';
        }

        return $text;
    }
}
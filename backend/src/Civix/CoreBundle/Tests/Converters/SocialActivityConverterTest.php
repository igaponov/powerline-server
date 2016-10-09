<?php
namespace Civix\CoreBundle\Tests\Converters;

use Civix\CoreBundle\Converters\SocialActivityConverter;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;

class SocialActivityConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $type
     * @param $html
     * @dataProvider getHTML
     */
    public function testToHTML($type, $html)
    {
        $this->assertEquals($html, SocialActivityConverter::toHTML($this->createActivity($type)));
    }

    public function getHTML()
    {
        return [
            [
                SocialActivity::TYPE_FOLLOW_REQUEST,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> wants to follow you</p>',
            ],
            [
                SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED,
                '<p>Request to join <strong>&lt;US&gt;</strong> has been approved</p>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_CREATED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> posted in the <strong>&lt;US&gt;</strong> community</p>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> created a petition in the <strong>&lt;US&gt;</strong> community</p>',
            ],
            [
                SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED,
                '<p>Permissions changed for <strong>&lt;US&gt;</strong></p>',
            ],
            [
                SocialActivity::TYPE_COMMENT_REPLIED,
                '<p><strong>John &lt;Doe&gt;</strong> replied to your comment</p>',
            ],
            [
                SocialActivity::TYPE_COMMENT_MENTIONED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> mentioned you in a comment</p>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
                '<p><strong>John &lt;Doe&gt;</strong> commented on Label in the <strong>&lt;US&gt;</strong> community</p>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_COMMENTED,
                '<p><strong>John &lt;Doe&gt;</strong> commented on the post you subscribed to</p>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
                '<p><strong>John &lt;Doe&gt;</strong> commented on Label in the <strong>&lt;US&gt;</strong> community</p>',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_COMMENTED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> commented on your poll</p>',
            ],
            [
                SocialActivity::TYPE_OWN_POST_COMMENTED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> commented on your post</p>',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> commented on your petition</p>',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_ANSWERED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> responded to a Label "&lt;Preview&gt;'.str_repeat('r', 400).'" in the <strong>&lt;US&gt;</strong> community</p>',
            ],
            [
                SocialActivity::TYPE_OWN_POST_VOTED,
                '<p><strong>&lt;Jane&gt; &lt;Roe&gt;</strong> voted on your post</p>',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_SIGNED,
                '<p><strong>John &lt;Doe&gt;</strong> signed your petition</p>',
            ],
        ];
    }

    /**
     * @param $type
     * @param $text
     * @dataProvider getText
     */
    public function testToText($type, $text)
    {
        $this->assertEquals($text, SocialActivityConverter::toText($this->createActivity($type)));
    }

    public function getText()
    {
        return [
            [
                SocialActivity::TYPE_FOLLOW_REQUEST,
                'wants to follow you. Approve?',
            ],
            [
                SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED,
                'Request to join <US> has been approved',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_CREATED,
                'posted: '.str_repeat('b', 300).'...',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED,
                str_repeat('b', 300).'...',
            ],
            [
                SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED,
                '<US> has changed the information it is asking for from you as a group member. Open to learn more.',
            ],
            [
                SocialActivity::TYPE_COMMENT_REPLIED,
                ' replied and said <Preview>'.str_repeat('r', 273).'...',
            ],
            [
                SocialActivity::TYPE_COMMENT_MENTIONED,
                'mentioned you in a comment',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
                ' commented on your poll',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_COMMENTED,
                ' commented on the post you subscribed to',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
                ' commented on the petition you subscribed to',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_COMMENTED,
                'commented on your poll',
            ],
            [
                SocialActivity::TYPE_OWN_POST_COMMENTED,
                'commented on your post',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED,
                'commented on your petition',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_ANSWERED,
                'responded to your poll',
            ],
            [
                SocialActivity::TYPE_OWN_POST_VOTED,
                'voted on your post',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_SIGNED,
                ' signed your petition',
            ],
        ];
    }

    /**
     * @param $type
     * @param $title
     * @dataProvider getTitle
     */
    public function testToTitle($type, $title)
    {
        $this->assertEquals($title, SocialActivityConverter::toTitle($this->createActivity($type)));
    }

    public function getTitle()
    {
        return [
            [
                SocialActivity::TYPE_FOLLOW_REQUEST,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED,
                '<US>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_CREATED,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED,
                '<Jane> <Roe> Petition',
            ],
            [
                SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED,
                'Group Permissions Changed',
            ],
            [
                SocialActivity::TYPE_COMMENT_REPLIED,
                'John <Doe>',
            ],
            [
                SocialActivity::TYPE_COMMENT_MENTIONED,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
                'John <Doe>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_COMMENTED,
                'John <Doe>',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
                'John <Doe>',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_COMMENTED,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_OWN_POST_COMMENTED,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_ANSWERED,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_OWN_POST_VOTED,
                '<Jane> <Roe>',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_SIGNED,
                'John <Doe>',
            ],
        ];
    }

    /**
     * @param $type
     * @param $title
     * @dataProvider getImage
     */
    public function testToImage($type, $title)
    {
        $this->assertEquals($title, SocialActivityConverter::toImage($this->createActivity($type)));
    }

    public function getImage()
    {
        return [
            [
                SocialActivity::TYPE_FOLLOW_REQUEST,
                '/image',
            ],
            [
                SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED,
                '/group.jpg',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_CREATED,
                '/image',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED,
                '/image',
            ],
            [
                SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED,
                '/group.jpg',
            ],
            [
                SocialActivity::TYPE_COMMENT_REPLIED,
                '/avatar.jpg',
            ],
            [
                SocialActivity::TYPE_COMMENT_MENTIONED,
                '/image',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
                '/avatar.jpg',
            ],
            [
                SocialActivity::TYPE_FOLLOW_POST_COMMENTED,
                '/avatar.jpg',
            ],
            [
                SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
                '/avatar.jpg',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_COMMENTED,
                '/image',
            ],
            [
                SocialActivity::TYPE_OWN_POST_COMMENTED,
                '/image',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED,
                '/image',
            ],
            [
                SocialActivity::TYPE_OWN_POLL_ANSWERED,
                '/image',
            ],
            [
                SocialActivity::TYPE_OWN_POST_VOTED,
                '/image',
            ],
            [
                SocialActivity::TYPE_OWN_USER_PETITION_SIGNED,
                '/avatar.jpg',
            ],
        ];
    }

    /**
     * @param $type
     * @return SocialActivity
     */
    private function createActivity($type)
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('<Doe>')
            ->setAvatarFileName('/avatar.jpg');
        $group = new Group();
        $group->setOfficialName('<US>')
            ->setAvatarFileName('/group.jpg');
        $activity = new SocialActivity($type, $user, $group);
        $activity->setTarget(
            [
                'body' => str_repeat('b', 400),
                'label' => 'Label',
                'first_name' => '<Jane>',
                'last_name' => '<Roe>',
                'full_name' => '<Jane> <Roe>',
                'preview' => '<Preview>'.str_repeat('r', 400),
                'image' => '/image',
            ]
        );

        return $activity;
    }
}
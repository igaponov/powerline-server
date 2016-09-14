<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Converters\SocialActivityConverter;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\SocialActivityRepository")
 * @ORM\Table(name="social_activities")
 */
class SocialActivity
{
    const TYPE_FOLLOW_REQUEST = 'follow-request';
    const TYPE_JOIN_TO_GROUP_APPROVED = 'joinToGroup-approved';
    const TYPE_GROUP_USER_PETITION_CREATED = 'user-petition-created';
    const TYPE_GROUP_POST_CREATED = 'post-created';
    const TYPE_OWN_POLL_ANSWERED = 'own-poll-answered';
    const TYPE_FOLLOW_POLL_COMMENTED = 'follow-pollCommented';
    const TYPE_COMMENT_REPLIED = 'comment-replied';
    const TYPE_FOLLOW_USER_PETITION_COMMENTED = 'follow-micropetitionCommented';
    const TYPE_FOLLOW_POST_COMMENTED = 'follow-postCommented';
    const TYPE_GROUP_PERMISSIONS_CHANGED = 'groupPermissions-changed';
    const TYPE_COMMENT_MENTIONED = 'comment-mentioned';
    const TYPE_OWN_POST_COMMENTED = 'own-post-commented';
    const TYPE_OWN_USER_PETITION_COMMENTED = 'own-user-petition-commented';
    const TYPE_OWN_USER_PETITION_SIGNED = 'own-user-petition-signed';
    const TYPE_OWN_POST_VOTED = 'own-post-voted';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $id;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $group;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="recipient_id",  referencedColumnName="id", onDelete="CASCADE")
     */
    private $recipient;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="following_id",  referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $following;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    private $createdAt;

    /**
     * @var array
     *
     * @ORM\Column(name="target", type="array")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $target;

    /**
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @ORM\Column(name="is_ignore", type="boolean", nullable=true)
     */
    private $ignore;

    private static $youTabTypes = [
        self::TYPE_FOLLOW_REQUEST,
        self::TYPE_JOIN_TO_GROUP_APPROVED,
        self::TYPE_COMMENT_REPLIED,
        self::TYPE_GROUP_PERMISSIONS_CHANGED,
        self::TYPE_COMMENT_MENTIONED,
    ];

    public function __construct($type = null, User $following = null, Group $group = null)
    {
        $this->type = $type;
        $this->following = $following;
        $this->group = $group;
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param User $recipient
     *
     * @return $this
     */
    public function setRecipient(User $recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group $group
     *
     * @return $this
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return array
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param array $target
     *
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIgnore()
    {
        return $this->ignore;
    }

    /**
     * @param bool $ignore
     *
     * @return $this
     */
    public function setIgnore($ignore)
    {
        $this->ignore = $ignore;

        return $this;
    }

    /**
     * @return User
     */
    public function getFollowing()
    {
        return $this->following;
    }

    /**
     * @param User $following
     *
     * @return $this
     */
    public function setFollowing(User $following)
    {
        $this->following = $following;

        return $this;
    }

    /**
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("string")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("tab")
     */
    public function getActivityTab()
    {
        return in_array($this->type, self::$youTabTypes) ? 'you' : 'following';
    }

    /**
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("string")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("html_message")
     */
    public function getHTMLMessage()
    {
        return SocialActivityConverter::toHTML($this);
    }

    public function getTextMessage()
    {
        return SocialActivityConverter::toText($this);
    }

    public function getTitle()
    {
        return SocialActivityConverter::toTitle($this);
    }

    public function getImage()
    {
        return SocialActivityConverter::toImage($this);
    }
} 
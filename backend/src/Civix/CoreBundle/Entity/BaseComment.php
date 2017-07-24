<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Comments entity.
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class BaseComment implements HtmlBodyInterface, UserMentionableInterface
{
    use UserMentionableTrait;

    const PRIVACY_PUBLIC = 0;
    const PRIVACY_PRIVATE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-parent", "api-comments-add"})
     * @Serializer\Type("integer")
     */
    protected $id;

    /**
     * @ORM\Column(name="comment_body", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-add", "api-comments-update"})
     * @Serializer\Type("string")
     * @Assert\NotBlank()
     * @Assert\Length(max=500)
     */
    protected $commentBody;

    /**
     * @ORM\Column(name="comment_body_html", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-add"})
     * @Serializer\Type("string")
     */
    protected $commentBodyHtml;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-add"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    protected $createdAt;

    /**
     * @var BaseComment
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments-add"})
     * @Serializer\Type("integer")
     * @Serializer\Until("1")
     */
    protected $parentComment;

    /**
     * @var ArrayCollection|BaseComment[]
     */
    protected $childrenComments;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments-add"})
     */
    protected $user;

    /**
     * @var BaseCommentRate[]|ArrayCollection
     */
    protected $rates;

    /**
     * @ORM\Column(name="rate_sum", type="integer")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Type("integer")
     */
    protected $rateSum = 0;

    /**
     * @ORM\Column(name="rates_count", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Until("2")
     */
    protected $ratesCount = 0;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Until("1")
     */
    protected $rateStatus;

    protected $isOwner;

    /**
     * @var int
     *
     * @ORM\Column(name="privacy", type="integer")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-add", "api-comments-update"})
     * @Serializer\Type("integer")
     * @Serializer\Until("1")
     */
    protected $privacy = self::PRIVACY_PUBLIC;

    /**
     * @var int
     */
    protected $childCount = 0;

    public static function getPrivacyTypes()
    {
        return [
            self::PRIVACY_PUBLIC,
            self::PRIVACY_PRIVATE,
        ];
    }

    public static function getPrivacyLabels()
    {
        return [
            self::PRIVACY_PUBLIC => 'public',
            self::PRIVACY_PRIVATE => 'private',
        ];
    }

    /**
     * @return CommentedInterface
     */
    abstract public function getCommentedEntity();

    /**
     * Return entity type
     *
     * @return string
     */
    abstract public function getEntityType(): string;

    public function __construct()
    {
        $this->rates = new ArrayCollection();
        $this->childrenComments = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set commentBody.
     *
     * @param string $commentBody
     * 
     * @return BaseComment
     */
    public function setCommentBody($commentBody)
    {
        $this->commentBody = $commentBody;

        return $this;
    }

    /**
     * Get commentBody.
     *
     * @return string
     */
    public function getCommentBody()
    {
        return $this->commentBody;
    }

    /**
     * Set parentComment.
     *
     * @param BaseComment $parentComment
     * 
     * @return BaseComment
     */
    public function setParentComment(BaseComment $parentComment = null)
    {
        $this->parentComment = $parentComment;

        return $this;
    }

    /**
     * Get parentComment.
     *
     * @return BaseComment
     */
    public function getParentComment()
    {
        return $this->parentComment;
    }

    /**
     * Set user.
     *
     * @param User $user
     * 
     * @return BaseComment
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set crate sum.
     *
     * @param int $rateSum
     *
     * @return BaseComment
     */
    public function setRateSum($rateSum)
    {
        $this->rateSum = $rateSum;

        return $this;
    }

    /**
     * Get rate sum.
     *
     * @return int
     */
    public function getRateSum()
    {
        return $this->rateSum;
    }

    /**
     * @deprecated
     * @param $userStatus
     * @return $this
     */
    public function setRateStatus($userStatus)
    {
        $this->rateStatus = $userStatus;

        return $this;
    }

    /**
     * @deprecated
     * @return mixed
     */
    public function getRateStatus()
    {
        return $this->rateStatus;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2")
     * @Serializer\Groups({"comments-rate"})
     * @Serializer\SerializedName("rate_value")
     * @Serializer\Type("string")
     */
    public function getUserRateStatus()
    {
        if ($this->rates->count()) {
            return $this->rates[0]->getRateValueLabel();
        }

        return '';
    }

    /**
     * @return int|null
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-comments", "api-comments-add"})
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("parent_comment")
     */
    public function getParentId(): ?int
    {
        return $this->parentComment ? $this->parentComment->getId() : null;
    }

    /**
     * Set privacy.
     *
     * @param int $privacy
     *
     * @return BaseComment
     */
    public function setPrivacy($privacy)
    {
        $this->privacy = $privacy === self::PRIVACY_PRIVATE ? self::PRIVACY_PRIVATE : self::PRIVACY_PUBLIC;

        return $this;
    }

    /**
     * Get privacy.
     *
     * @return int
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->privacy === self::PRIVACY_PRIVATE;
    }

    /**
     * @deprecated
     * @param $status
     * @return $this
     */
    public function setIsOwner($status)
    {
        $this->isOwner = $status;

        return $this;
    }

    /**
     * @deprecated
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api-comments"})
     * @Serializer\SerializedName("is_owner")
     * @Serializer\Until("1")
     */
    public function getIsOwner()
    {
        return $this->isOwner;
    }

    /**
     * @return User
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2")
     * @Serializer\Type("Owner")
     * @Serializer\Groups({"api-comments"})
     * @Serializer\SerializedName("is_owner")
     */
    public function getIsUserOwner()
    {
        return $this->user;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Type("Avatar")
     * @Serializer\SerializedName("author_picture")
     */
    public function getCommentPicture()
    {
        return $this->privacy === self::PRIVACY_PUBLIC ?
            ($this->user instanceof User ? $this->user->getAvatarWithPath() : null):
            $this->user->getAvatarWithPath($this->privacy)
        ;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api-comments"})
     * @Serializer\SerializedName("user")
     * @Serializer\Type("Civix\CoreBundle\Entity\User")
     */
    public function getUserInfo()
    {
        return $this->privacy === self::PRIVACY_PUBLIC ? $this->user : null;
    }

    /**
     * Add childrenComments.
     *
     * @param BaseComment $childrenComments
     *
     * @return BaseComment
     */
    public function addChildrenComment(BaseComment $childrenComments)
    {
        $this->childrenComments[] = $childrenComments;

        return $this;
    }

    /**
     * Remove childrenComments.
     *
     * @param BaseComment $childrenComments
     */
    public function removeChildrenComment(BaseComment $childrenComments)
    {
        $this->childrenComments->removeElement($childrenComments);
    }

    /**
     * Get childrenComments.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildrenComments()
    {
        return $this->childrenComments;
    }

    /**
     * @return BaseCommentRate[]|ArrayCollection
     */
    public function getRates()
    {
        return $this->rates;
    }

    /**
     * Add rate.
     *
     * @param BaseCommentRate $rate
     *
     * @return BaseComment
     */
    public function addRate(BaseCommentRate $rate)
    {
        $this->rates[] = $rate;
        $rate->setComment($this);

        return $this;
    }

    /**
     * Remove rate.
     *
     * @param BaseCommentRate $rate
     */
    public function removeRate(BaseCommentRate $rate)
    {
        $this->rates->removeElement($rate);
    }

    /**
     * @param mixed $ratesCount
     *
     * @return $this
     */
    public function setRatesCount($ratesCount)
    {
        $this->ratesCount = $ratesCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCommentBodyHtml()
    {
        return $this->commentBodyHtml;
    }

    /**
     * @param mixed $commentBodyHtml
     *
     * @return $this
     */
    public function setCommentBodyHtml($commentBodyHtml)
    {
        $this->commentBodyHtml = $commentBodyHtml;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRatesCount()
    {
        return $this->ratesCount;
    }

    /**
     * Renamed ratesCount
     *
     * @return int
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2.2")
     * @Serializer\Type("integer")
     * @Serializer\Groups({"api-comments"})
     */
    public function getRateCount(): int
    {
        return $this->ratesCount;
    }

    public function getRateUp()
    {
        return $this->ratesCount ? ($this->ratesCount + $this->rateSum) / 2 : 0;
    }

    public function getRateDown()
    {
        return $this->ratesCount ? ($this->ratesCount - $this->rateSum) / 2 : 0;
    }

    public function getBody()
    {
        return $this->getCommentBody();
    }

    public function setHtmlBody($html)
    {
        $this->setCommentBodyHtml($html);
    }

    /**
     * @return string|null
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2")
     * @Serializer\SerializedName("privacy")
     * @Serializer\Type("string")
     * @Serializer\Groups({"api-comments", "api-comments-add", "api-comments-update"})
     */
    public function getPrivacyLabel()
    {
        $labels = self::getPrivacyLabels();
        if (isset($labels[$this->privacy])) {
            return $labels[$this->privacy];
        }

        return null;
    }

    /**
     * @deprecated Will be removed in next version
     * @return bool
     *
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("is_root")
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Until("2")
     */
    public function isRoot()
    {
        return !$this->getParentComment();
    }

    /**
     * @return int
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2.2")
     * @Serializer\Type("integer")
     */
    public function getChildCount(): int
    {
        if (!$this->childCount) {
            $this->childCount = $this->getChildrenComments()->count();
        }

        return $this->childCount;
    }

    /**
     * @param int $childCount
     * @return BaseComment
     */
    public function setChildCount(int $childCount): BaseComment
    {
        $this->childCount = $childCount;

        return $this;
    }

    /**
     * Returns children only for a root comment.
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Type("array<Civix\CoreBundle\Entity\BaseComment>")
     * @Serializer\Since("2.2")
     *
     * @internal use only for serialization
     *
     * @return BaseComment[]|Collection
     */
    public function getChildren(): Collection
    {
        return $this->getParentId() ? new ArrayCollection() : $this->childrenComments;
    }
}

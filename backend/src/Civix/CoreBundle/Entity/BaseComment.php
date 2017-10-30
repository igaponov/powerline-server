<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Serializer\Type\Avatar;
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
abstract class BaseComment implements HtmlBodyInterface
{
    const PRIVACY_PUBLIC = 0;
    const PRIVACY_PRIVATE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-parent", "api-comments-add", "activity-list"})
     * @Serializer\Type("integer")
     */
    protected $id;

    /**
     * @ORM\Column(name="comment_body", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-add", "api-comments-update", "activity-list"})
     * @Serializer\Type("string")
     * @Assert\NotBlank()
     * @Assert\Length(max=500)
     */
    protected $commentBody = '';

    /**
     * @ORM\Column(name="comment_body_html", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-add", "activity-list"})
     * @Serializer\Type("string")
     */
    protected $commentBodyHtml = '';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "api-comments-add", "activity-list"})
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
     *
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
     * @Serializer\Groups({"api-comments", "activity-list"})
     * @Serializer\Type("integer")
     */
    protected $rateSum = 0;

    /**
     * @ORM\Column(name="rates_count", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments", "activity-list"})
     * @Serializer\Until("2")
     */
    protected $ratesCount = 0;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Until("1")
     */
    protected $rateStatus;

    protected $isOwner = false;

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
    abstract public function getCommentedEntity(): CommentedInterface;

    /**
     * Return entity type
     *
     * @return string
     */
    abstract public function getEntityType(): string;

    public function __construct(User $user, BaseComment $parentComment = null)
    {
        $this->user = $user;
        $this->parentComment = $parentComment;
        $this->rates = new ArrayCollection();
        $this->childrenComments = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
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
    public function setCommentBody(string $commentBody): BaseComment
    {
        $this->commentBody = $commentBody;

        return $this;
    }

    /**
     * Get commentBody.
     *
     * @return string
     */
    public function getCommentBody(): string
    {
        return $this->commentBody;
    }

    /**
     * Set parentComment.
     *
     * @param BaseComment $parentComment
     * @return BaseComment
     * @throws \DomainException
     * @deprecated Use __construct to set parent comment
     */
    public function setParentComment(BaseComment $parentComment): self
    {
        if (!$parentComment instanceof static) {
            throw new \DomainException('Parent comment should be instance of ' . static::class);
        }
        $this->parentComment = $parentComment;

        return $this;
    }

    /**
     * Get parentComment.
     *
     * @return BaseComment
     */
    public function getParentComment(): ?BaseComment
    {
        return $this->parentComment;
    }

    /**
     * Set user.
     *
     * @param User $user
     * 
     * @return BaseComment
     * @deprecated Use __construct to set user attribute
     */
    public function setUser(User $user): BaseComment
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
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
    public function setRateSum(int $rateSum): BaseComment
    {
        $this->rateSum = $rateSum;

        return $this;
    }

    /**
     * Get rate sum.
     *
     * @return int
     */
    public function getRateSum(): int
    {
        return $this->rateSum;
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
    public function setPrivacy(int $privacy): BaseComment
    {
        $this->privacy = $privacy === self::PRIVACY_PRIVATE ? self::PRIVACY_PRIVATE : self::PRIVACY_PUBLIC;

        return $this;
    }

    /**
     * Get privacy.
     *
     * @return int
     */
    public function getPrivacy(): int
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
    public function setIsOwner(bool $status)
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
    public function getIsOwner(): bool
    {
        return $this->isOwner;
    }

    /**
     * @return User
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2")
     * @Serializer\Type("Owner")
     * @Serializer\Groups({"api-comments", "activity-list"})
     * @Serializer\SerializedName("is_owner")
     */
    public function getIsUserOwner(): User
    {
        return $this->user;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api-comments", "activity-list"})
     * @Serializer\Type("Avatar")
     * @Serializer\SerializedName("author_picture")
     */
    public function getCommentPicture(): Avatar
    {
        return $this->privacy === self::PRIVACY_PUBLIC ?
            ($this->user instanceof User ? $this->user->getAvatarWithPath() : null):
            $this->user->getAvatarWithPath($this->privacy)
        ;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api-comments", "activity-list"})
     * @Serializer\SerializedName("user")
     * @Serializer\Type("Civix\CoreBundle\Entity\User")
     */
    public function getUserInfo(): ?User
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
    public function addChildrenComment(BaseComment $childrenComments): BaseComment
    {
        $this->childrenComments[] = $childrenComments;

        return $this;
    }

    /**
     * Remove childrenComments.
     *
     * @param BaseComment $childrenComments
     */
    public function removeChildrenComment(BaseComment $childrenComments): void
    {
        $this->childrenComments->removeElement($childrenComments);
    }

    /**
     * Get childrenComments.
     *
     * @return BaseComment[]|Collection
     */
    public function getChildrenComments(): Collection
    {
        return $this->childrenComments;
    }

    /**
     * @return BaseCommentRate[]|Collection
     */
    public function getRates(): Collection
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
    public function addRate(BaseCommentRate $rate): BaseComment
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
    public function removeRate(BaseCommentRate $rate): void
    {
        $this->rates->removeElement($rate);
    }

    /**
     * @param int $ratesCount
     *
     * @return $this
     */
    public function setRatesCount(int $ratesCount): BaseComment
    {
        $this->ratesCount = $ratesCount;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommentBodyHtml(): string
    {
        return $this->commentBodyHtml;
    }

    /**
     * @param string $commentBodyHtml
     *
     * @return $this
     */
    public function setCommentBodyHtml(string $commentBodyHtml)
    {
        $this->commentBodyHtml = $commentBodyHtml;

        return $this;
    }

    /**
     * @return int
     */
    public function getRatesCount(): int
    {
        return $this->ratesCount;
    }

    /**
     * Renamed ratesCount
     *
     * @internal Use only for serialization
     * @return int
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2.2")
     * @Serializer\Type("integer")
     * @Serializer\Groups({"api-comments", "activity-list"})
     */
    public function getRateCount(): int
    {
        return $this->ratesCount;
    }

    public function getRateUp(): float
    {
        return $this->ratesCount ? ($this->ratesCount + $this->rateSum) / 2 : 0;
    }

    public function getRateDown(): float
    {
        return $this->ratesCount ? ($this->ratesCount - $this->rateSum) / 2 : 0;
    }

    public function getBody(): ?string
    {
        return $this->getCommentBody();
    }

    public function setHtmlBody(string $html): BaseComment
    {
        $this->setCommentBodyHtml($html);

        return $this;
    }

    /**
     * @return string|null
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2")
     * @Serializer\SerializedName("privacy")
     * @Serializer\Type("string")
     * @Serializer\Groups({"api-comments", "api-comments-add", "api-comments-update", "activity-list"})
     */
    public function getPrivacyLabel(): string
    {
        return self::getPrivacyLabels()[$this->privacy];
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
    public function isRoot(): bool
    {
        return !$this->getParentComment();
    }

    /**
     * @return int
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-comments"})
     * @Serializer\Type("integer")
     * @Serializer\Since("2.2")
     */
    public function getChildCount(): int
    {
        if ($this->getParentId()) {
            return 0;
        }
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

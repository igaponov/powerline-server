<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Comments entity.
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class BaseComment
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
     * @Serializer\Groups({"api-comments", "api-comments-parent", "api-comments-add"})
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
     * @Serializer\Groups({"api-comments", "api-comments-add"})
     * @Serializer\Type("integer")
     * @Serializer\Accessor(getter="getParentId")
     */
    protected $parentComment;

    /**
     * @var ArrayCollection|BaseComment[]
     */
    protected $childrenComments;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments-add"})
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="\Civix\CoreBundle\Entity\Poll\CommentRate", mappedBy="comment")
     */
    protected $rates;

    /**
     * @ORM\Column(name="rate_sum", type="integer")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments"})
     */
    protected $rateSum = 0;

    /**
     * @ORM\Column(name="rates_count", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments"})
     */
    protected $ratesCount = 0;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-comments"})
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
     */
    protected $privacy = self::PRIVACY_PUBLIC;

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

    public function setRateStatus($userStatus)
    {
        $this->rateStatus = $userStatus;

        return $this;
    }

    public function getRateStatus()
    {
        return $this->rateStatus;
    }

    public function getRates()
    {
        return $this->rates;
    }

    public function getParentId()
    {
        if (isset($this->parentComment)) {
            return $this->parentComment->getId();
        }

        return 0;
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

    public function setIsOwner($status)
    {
        $this->isOwner = $status;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api-comments"})
     * @Serializer\SerializedName("is_owner")
     */
    public function getIsOwner()
    {
        return $this->isOwner;
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
     * Add rates.
     *
     * @param Poll\CommentRate $rates
     *
     * @return BaseComment
     */
    public function addRate(Poll\CommentRate $rates)
    {
        $this->rates[] = $rates;

        return $this;
    }

    /**
     * Remove rates.
     *
     * @param Poll\CommentRate $rates
     */
    public function removeRate(Poll\CommentRate $rates)
    {
        $this->rates->removeElement($rates);
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

    public function getRateUp()
    {
        return $this->ratesCount ? ($this->ratesCount + $this->rateSum) / 2 : 0;
    }

    public function getRateDown()
    {
        return $this->ratesCount ? ($this->ratesCount - $this->rateSum) / 2 : 0;
    }
}

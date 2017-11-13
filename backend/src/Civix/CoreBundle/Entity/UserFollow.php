<?php

namespace Civix\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

/**
 * User follower.
 *
 * @ORM\Table(
 *      name="users_follow",
 *      uniqueConstraints=
 *      {
 *          @ORM\UniqueConstraint(name="unique_follow", columns={"user_id", "follower_id"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\UserFollowRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class UserFollow
{
    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-follow"})
     * @Serializer\Until("1")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_create", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Groups({"api-followers", "api-following", "api-follow"})
     */
    private $dateCreate;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_approval", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-followers", "api-following", "api-follow"})
     */
    private $dateApproval;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="followers", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-following", "api-follow", "api-follow-create"})
     */
    private $user;

    /**
     * Deprecated, this property is serialized inline in v2
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="following", cascade={"persist"})
     * @ORM\JoinColumn(name="follower_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-followers", "api-follow"})
     */
    private $follower;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     * @Serializer\Expose()
     * @Serializer\Until("1")
     * @Serializer\Groups({"api-followers", "api-following", "api-follow"})
     */
    private $status;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = true})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-followers", "api-following", "api-follow"})
     */
    private $notifying = true;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", name="do_not_disturb_till", options={"default" = "CURRENT_TIMESTAMP"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-followers", "api-following", "api-follow", "api-follow-create"})
     */
    private $doNotDisturbTill;

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'pending',
            self::STATUS_ACTIVE => 'active',
        ];
    }

    public function __construct()
    {
        $this->doNotDisturbTill = new DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return UserFollow
     */
    public function setStatus(int $status): UserFollow
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string|null
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2")
     * @Serializer\SerializedName("status")
     * @Serializer\Type("string")
     * @Serializer\Groups({"api-info", "api-follow"})
     */
    public function getStatusLabel(): ?string
    {
        $labels = static::getStatusLabels();
        if (isset($labels[$this->status])) {
            return $labels[$this->status];
        }

        return null;
    }

    /**
     * Set date create.
     *
     * @param DateTime $date
     *
     * @return UserFollow
     */
    public function setDateCreate(DateTime $date): UserFollow
    {
        $this->dateCreate = $date;

        return $this;
    }

    /**
     * Get date create.
     *
     * @return DateTime
     */
    public function getDateCreate(): DateTime
    {
        return $this->dateCreate;
    }

    /**
     * Set date approval.
     *
     * @param DateTime $date
     *
     * @return UserFollow
     */
    public function setDateApproval(DateTime $date): UserFollow
    {
        $this->dateApproval = $date;

        return $this;
    }

    /**
     * Get date approval.
     *
     * @return DateTime
     */
    public function getDateApproval(): ?DateTime
    {
        return $this->dateApproval;
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return UserFollow
     */
    public function setUser(User $user): UserFollow
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
     * Set follower.
     *
     * @param User $follower
     *
     * @return UserFollow
     */
    public function setFollower(User $follower): UserFollow
    {
        $this->follower = $follower;

        return $this;
    }

    /**
     * Get follower.
     *
     * @return User
     */
    public function getFollower(): User
    {
        return $this->follower;
    }

    /**
     * Approve following request
     * 
     * @return $this
     */
    public function approve(): UserFollow
    {
        $this->status = self::STATUS_ACTIVE;
        $this->dateApproval = new DateTime();
        
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Inline()
     * @Serializer\Type("Civix\CoreBundle\Entity\User")
     * @Serializer\Since("2")
     * @Serializer\Groups({"api-following"})
     *
     * @return User
     */
    public function getInlineUser(): User
    {
        return $this->user;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Inline()
     * @Serializer\Type("Civix\CoreBundle\Entity\User")
     * @Serializer\Since("2")
     * @Serializer\Groups({"api-followers"})
     *
     * @return User
     */
    public function getInlineFollower(): User
    {
        return $this->follower;
    }

    /**
     * @return bool
     */
    public function isNotifying(): bool
    {
        return $this->notifying;
    }

    /**
     * @param bool $notifying
     * @return UserFollow
     */
    public function setNotifying(bool $notifying): UserFollow
    {
        $this->notifying = $notifying;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDoNotDisturbTill(): DateTime
    {
        return $this->doNotDisturbTill;
    }

    /**
     * @param DateTime $doNotDisturbTill
     * @return UserFollow
     */
    public function setDoNotDisturbTill(DateTime $doNotDisturbTill): UserFollow
    {
        $this->doNotDisturbTill = $doNotDisturbTill;

        return $this;
    }
}

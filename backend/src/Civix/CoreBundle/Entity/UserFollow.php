<?php

namespace Civix\CoreBundle\Entity;

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
 * @ORM\HasLifecycleCallbacks()
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
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_create", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Groups({"api-followers", "api-following", "api-follow"})
     */
    private $dateCreate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_approval", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-followers", "api-following", "api-follow"})
     */
    private $dateApproval;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="followers", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-following", "api-follow", "api-follow-create"})
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="following", cascade={"persist"})
     * @ORM\JoinColumn(name="follower_id", referencedColumnName="id", onDelete="cascade")
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

    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => 'pending',
            self::STATUS_ACTIVE => 'active',
        ];
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
     * Set status.
     *
     * @param int $status
     *
     * @return UserFollow
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
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
    public function getStatusLabel()
    {
        $labels = $this->getStatusLabels();
        if (isset($labels[$this->status])) {
            return $labels[$this->status];
        } else {
            return null;
        }
    }

    /**
     * Set date create.
     *
     * @param \DateTime $date
     *
     * @return UserFollow
     */
    public function setDateCreate(\DateTime $date)
    {
        $this->dateCreate = $date;

        return $this;
    }

    /**
     * Get date create.
     *
     * @return \DateTime
     */
    public function getDateCreate()
    {
        return $this->dateCreate;
    }

    /**
     * Set date approval.
     *
     * @param \DateTime $date
     *
     * @return UserFollow
     */
    public function setDateApproval(\DateTime $date)
    {
        $this->dateApproval = $date;

        return $this;
    }

    /**
     * Get date approval.
     *
     * @return \DateTime
     */
    public function getDateApproval()
    {
        return $this->dateApproval;
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\User $user
     *
     * @return UserFollow
     */
    public function setUser(\Civix\CoreBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set follower.
     *
     * @param \Civix\CoreBundle\Entity\User $follower
     *
     * @return UserFollow
     */
    public function setFollower(\Civix\CoreBundle\Entity\User $follower)
    {
        $this->follower = $follower;

        return $this;
    }

    /**
     * Get follower.
     *
     * @return \Civix\CoreBundle\Entity\User
     */
    public function getFollower()
    {
        return $this->follower;
    }

    /**
     * Approve following request
     * 
     * @return $this
     */
    public function approve()
    {
        $this->status = self::STATUS_ACTIVE;
        $this->dateApproval = new \DateTime();
        
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getStatus() == self::STATUS_ACTIVE;
    }
}

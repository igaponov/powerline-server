<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * User group managers for a group.
 *
 * @ORM\Table(
 *      name="users_groups_managers",
 *      uniqueConstraints=
 *      {
 *          @ORM\UniqueConstraint(name="unique_user_group_manager", columns={"user_id", "group_id"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\UserGroupManagerRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class UserGroupManager
{
    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-groups"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="managedGroups", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups"})
     */
    private $group;

    /**
     * @var \DateTime created_at
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-groups"})
     */
    private $status;

    public function __construct(User $user, Group $group)
    {
        $this->setUser($user);
        $this->setGroup($group);
        $this->setCreatedAt(new \DateTime());

        //set status according to membership control in group
        if ($group->getMembershipControl() == Group::GROUP_MEMBERSHIP_APPROVAL) {
            $this->setStatus(self::STATUS_PENDING);
        } else {
            $this->setStatus(self::STATUS_ACTIVE);
        }
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
     * @return UserGroupManager
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
     * Set user.
     *
     * @param User $user
     *
     * @return UserGroupManager
     */
    public function setUser(User $user)
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
     * Set group.
     *
     * @param Group $group
     *
     * @return UserGroupManager
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set createAt.
     *
     * @param \DateTime $createdAt
     *
     * @return UserGroupManager
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
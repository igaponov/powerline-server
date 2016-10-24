<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Serializer\Type\UserRole;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Group\GroupField;

/**
 * User follower.
 *
 * @ORM\Table(
 *      name="users_groups",
 *      uniqueConstraints=
 *      {
 *          @ORM\UniqueConstraint(name="unique_user_group", columns={"user_id", "group_id"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\UserGroupRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class UserGroup implements LeaderContentInterface
{
    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Until("1")
     * @Serializer\Groups({"api-info", "api-groups"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="groups", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Until("1")
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
     * @Serializer\Until("1")
     * @Serializer\Groups({"api-info", "api-groups"})
     */
    private $status;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_name", type="boolean", options={"default" = false})
     */
    private $permissionsName;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_contacts", type="boolean", options={"default" = false})
     *
     * @deprecated
     */
    private $permissionsContacts;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_address", type="boolean", options={"default" = false})
     */
    private $permissionsAddress;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_city", type="boolean", options={"default" = false})
     *
     */
    private $permissionsCity;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_state", type="boolean", options={"default" = false})
     *
     */
    private $permissionsState;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_country", type="boolean", options={"default" = false})
     *
     */
    private $permissionsCountry;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_zip_code", type="boolean", options={"default" = false})
     *
     */
    private $permissionsZipCode;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_email", type="boolean", options={"default" = false})
     */
    private $permissionsEmail;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_phone", type="boolean", options={"default" = false})
     */
    private $permissionsPhone;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @ORM\Column(name="permissions_responses", type="boolean", options={"default" = false})
     */
    private $permissionsResponses;

    /**
     * @var \DateTime created_at
     * @Serializer\Expose()
     * @Serializer\Groups({"api-permissions"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s'>")
     * @ORM\Column(name="permissions_approved_at", type="datetime", nullable=true)
     */
    private $permissionsApprovedAt;

    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => 'pending',
            self::STATUS_ACTIVE => 'active',
        ];
    }

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

        $this->setPermissionsName(false);
        $this->setPermissionsContacts(false);
        $this->setPermissionsEmail(false);
        $this->setPermissionsPhone(false);
        $this->setPermissionsAddress(false);
        $this->setPermissionsCity(false);
        $this->setPermissionsState(false);
        $this->setPermissionsCountry(false);
        $this->setPermissionsZipCode(false);
        $this->setPermissionsResponses(false);
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
     * @return UserGroup
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
     * @Serializer\SerializedName("join_status")
     * @Serializer\Type("string")
     * @Serializer\Groups({"api-info", "api-groups"})
     */
    public function getJoinStatus()
    {
        $labels = $this->getStatusLabels();
        if (isset($labels[$this->status])) {
            return $labels[$this->status];
        } else {
            return null;
        }
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return UserGroup
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
     * @return UserGroup
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
     * @return User
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Since("2")
     * @Serializer\Inline()
     * @Serializer\Groups({"api-info", "api-groups"})
     */
    public function getGroupInline()
    {
        return $this->group;
    }

    /**
     * Set createAt.
     *
     * @param \DateTime $createdAt
     *
     * @return UserGroup
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

    /**
     * Set permissionsName.
     *
     * @param bool $permissionsName
     *
     * @return UserGroup
     */
    public function setPermissionsName($permissionsName)
    {
        $this->permissionsName = $permissionsName;

        return $this;
    }

    /**
     * Get permissionsName.
     *
     * @return bool
     */
    public function getPermissionsName()
    {
        return $this->permissionsName;
    }

    /**
     * Set permissionsContacts.
     *
     * @param bool $permissionsContacts
     *
     * @return UserGroup
     */
    public function setPermissionsContacts($permissionsContacts)
    {
        $this->permissionsContacts = $permissionsContacts;

        return $this;
    }

    /**
     * Get permissionsContacts.
     *
     * @return bool
     */
    public function getPermissionsContacts()
    {
        return $this->permissionsContacts;
    }

    /**
     * Set permissionsResponses.
     *
     * @param bool $permissionsResponses
     *
     * @return UserGroup
     */
    public function setPermissionsResponses($permissionsResponses)
    {
        $this->permissionsResponses = $permissionsResponses;

        return $this;
    }

    /**
     * Get permissionsResponses.
     *
     * @return bool
     */
    public function getPermissionsResponses()
    {
        return $this->permissionsResponses;
    }

    /**
     * @return mixed
     */
    public function getPermissionsAddress()
    {
        return $this->permissionsAddress;
    }

    /**
     * @param mixed $permissionsAddress
     *
     * @return $this
     */
    public function setPermissionsAddress($permissionsAddress)
    {
        $this->permissionsAddress = $permissionsAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPermissionsEmail()
    {
        return $this->permissionsEmail;
    }

    /**
     * @param mixed $permissionsEmail
     *
     * @return $this
     */
    public function setPermissionsEmail($permissionsEmail)
    {
        $this->permissionsEmail = $permissionsEmail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPermissionsPhone()
    {
        return $this->permissionsPhone;
    }

    /**
     * @param mixed $permissionsPhone
     *
     * @return $this
     */
    public function setPermissionsPhone($permissionsPhone)
    {
        $this->permissionsPhone = $permissionsPhone;

        return $this;
    }

    /**
     * Set permissionsApprovedAt.
     *
     * @param \DateTime $permissionsApprovedAt
     *
     * @return UserGroup
     */
    public function setPermissionsApprovedAt($permissionsApprovedAt)
    {
        $this->permissionsApprovedAt = $permissionsApprovedAt;

        return $this;
    }

    /**
     * Get permissionsApprovedAt.
     *
     * @return \DateTime
     */
    public function getPermissionsApprovedAt()
    {
        return $this->permissionsApprovedAt;
    }

    public function setPermissionsByGroup(Group $group)
    {
        if (!$group->getRequiredPermissions()) {
            return $this;
        }
        foreach ($group->getRequiredPermissions() as $permissionKey) {
            $method = 'set'.(str_replace('_', '', $permissionKey));
            $this->$method(true);
        }

        return $this;
    }

    public function getUserDataRow()
    {
        $user = $this->getUser();
        $result = [
            $this->getPermissionsName() ? $user->getFullName() : '',
            $this->getPermissionsAddress() ? implode(', ', $user->getAddressArray()) : '',
            $this->getPermissionsEmail() ? $user->getEmail() : '',
            $this->getPermissionsPhone() ? $user->getPhone() : '',
            $user->getFacebookId() ? 'Yes' : 'No',
        ];

        /* @var GroupField $field */
        foreach ($this->getGroup()->getFields() as $field) {
            $result[] = $field->getUserValue($user);
        }
        $result[] = $this->createdAt->format(\DateTime::RFC822);
        $result[] = $user->getFollowers()->count();

        return $result;
    }

    /**
     * Set permissionsCity
     *
     * @param boolean $permissionsCity
     * @return UserGroup
     */
    public function setPermissionsCity($permissionsCity)
    {
        $this->permissionsCity = $permissionsCity;
    
        return $this;
    }

    /**
     * Get permissionsCity
     *
     * @return boolean 
     */
    public function getPermissionsCity()
    {
        return $this->permissionsCity;
    }

    /**
     * Set permissionsState
     *
     * @param boolean $permissionsState
     * @return UserGroup
     */
    public function setPermissionsState($permissionsState)
    {
        $this->permissionsState = $permissionsState;
    
        return $this;
    }

    /**
     * Get permissionsState
     *
     * @return boolean 
     */
    public function getPermissionsState()
    {
        return $this->permissionsState;
    }

    /**
     * Set permissionsCountry
     *
     * @param boolean $permissionsCountry
     * @return UserGroup
     */
    public function setPermissionsCountry($permissionsCountry)
    {
        $this->permissionsCountry = $permissionsCountry;
    
        return $this;
    }

    /**
     * Get permissionsCountry
     *
     * @return boolean 
     */
    public function getPermissionsCountry()
    {
        return $this->permissionsCountry;
    }

    /**
     * Set permissionsZipCode
     *
     * @param boolean $permissionsZipCode
     * @return UserGroup
     */
    public function setPermissionsZipCode($permissionsZipCode)
    {
        $this->permissionsZipCode = $permissionsZipCode;
    
        return $this;
    }

    /**
     * Get permissionsZipCode
     *
     * @return boolean 
     */
    public function getPermissionsZipCode()
    {
        return $this->permissionsZipCode;
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * @return UserRole
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"group-list"})
     * @Serializer\SerializedName("user_role")
     * @Serializer\Type("UserRole")
     */
    public function getUserRole()
    {
        return new UserRole($this);
    }
}
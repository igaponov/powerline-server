<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ActivityCondition.
 *
 * @ORM\Table(name="activity_condition")
 * @ORM\Entity
 */
class ActivityCondition
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    private $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="district_id", type="integer", nullable=true)
     */
    private $districtId;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_superuser", type="boolean", nullable=true)
     */
    private $isSuperuser;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="group_section_id", type="integer", nullable=true)
     */
    private $groupSectionId;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Activity")
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $activity;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="activity_condition_users",
     *      joinColumns={@ORM\JoinColumn(name="activity_condition_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $users;

    public function __construct(Activity $activity = null)
    {
        $this->setActivity($activity);
        $this->users = new ArrayCollection();
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
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return ActivityCondition
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set districtId.
     *
     * @param int $districtId
     *
     * @return ActivityCondition
     */
    public function setDistrictId($districtId)
    {
        $this->districtId = $districtId;

        return $this;
    }

    /**
     * Get districtId.
     *
     * @return int
     */
    public function getDistrictId()
    {
        return $this->districtId;
    }

    /**
     * Set isSuperuser.
     *
     * @param bool $isSuperuser
     *
     * @return ActivityCondition
     */
    public function setIsSuperuser($isSuperuser)
    {
        $this->isSuperuser = $isSuperuser;

        return $this;
    }

    /**
     * Get isSuperuser.
     *
     * @return bool
     */
    public function getIsSuperuser()
    {
        return $this->isSuperuser;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return ActivityCondition
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function setGroupSectionId($sectionId)
    {
        $this->groupSectionId = $sectionId;

        return $this;
    }

    public function getGroupSectionId()
    {
        return $this->groupSectionId;
    }

    /**
     * Set activity.
     *
     * @param \Civix\CoreBundle\Entity\Activity $activity
     *
     * @return ActivityCondition
     */
    public function setActivity(\Civix\CoreBundle\Entity\Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity.
     *
     * @return \Civix\CoreBundle\Entity\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $users
     *
     * @return $this
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function addUsers(User $user)
    {
        $this->users[] = $user;

        return $this;
    }
}

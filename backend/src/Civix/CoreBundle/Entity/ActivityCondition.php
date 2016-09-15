<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ActivityCondition.
 *
 * @ORM\Table(name="activity_condition")
 * @ORM\Entity()
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
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $group;

    /**
     * @var District
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\District")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $district;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_superuser", type="boolean", nullable=true)
     */
    private $isSuperuser;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @var GroupSection
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\GroupSection")
     * @ORM\JoinColumn(name="group_section_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $groupSection;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Activity")
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
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
     * Set group.
     *
     * @param Group $group
     *
     * @return ActivityCondition
     */
    public function setGroup($group)
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
     * Set district.
     *
     * @param District $district
     *
     * @return ActivityCondition
     */
    public function setDistrict($district)
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district.
     *
     * @return District
     */
    public function getDistrict()
    {
        return $this->district;
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
     * Set user.
     *
     * @param User $user
     *
     * @return ActivityCondition
     */
    public function setUser($user)
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
     * @param GroupSection $section
     * @return $this
     */
    public function setGroupSection($section)
    {
        $this->groupSection = $section;

        return $this;
    }

    /**
     * @return GroupSection
     */
    public function getGroupSection()
    {
        return $this->groupSection;
    }

    /**
     * Set activity.
     *
     * @param Activity $activity
     *
     * @return ActivityCondition
     */
    public function setActivity(Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity.
     *
     * @return Activity
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

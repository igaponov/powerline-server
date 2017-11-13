<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * @var Group|null
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $group;

    /**
     * @var District|null
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\District")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $district;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @var GroupSection|null
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

    public function __construct(Activity $activity = null)
    {
        $this->activity = $activity;
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
     * Set group.
     *
     * @param Group $group
     *
     * @return ActivityCondition
     */
    public function setGroup(Group $group): ActivityCondition
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return Group|null
     */
    public function getGroup(): ?Group
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
    public function setDistrict(District $district): ActivityCondition
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district.
     *
     * @return District|null
     */
    public function getDistrict(): ?District
    {
        return $this->district;
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return ActivityCondition
     */
    public function setUser(User $user): ActivityCondition
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param GroupSection $section
     * @return $this
     */
    public function setGroupSection($section): ActivityCondition
    {
        $this->groupSection = $section;

        return $this;
    }

    /**
     * @return GroupSection|null
     */
    public function getGroupSection(): ?GroupSection
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
    public function setActivity(Activity $activity): ActivityCondition
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity.
     *
     * @return Activity
     */
    public function getActivity(): Activity
    {
        return $this->activity;
    }
}

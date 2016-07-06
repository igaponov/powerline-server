<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\ActivityReadRepository")
 * @ORM\Table(
 *     name="activities_read",
 *     indexes={@ORM\Index(name="created_ind", columns={"created_at"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"activity_id", "user_id"})}
 * )
 * @Serializer\ExclusionPolicy("all")
 */
class ActivityRead
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Activity", inversedBy="activityRead")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $activity;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id",  referencedColumnName="id")
     */
    private $user;

    /**
     * @var \DateTime()
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     * @deprecated Use {@link getActivity()} instead
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("integer")
     * @Serializer\Until("1")
     * @Serializer\Groups({"api-activities"})
     */
    public function getActivityId()
    {
        return $this->getActivity()->getId();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param Activity $activity
     * @return ActivityRead
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }
}

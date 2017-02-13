<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="announcement_read",
 *     indexes={@ORM\Index(columns={"created_at"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"announcement_id", "user_id"})}
 * )
 * @Serializer\ExclusionPolicy("all")
 */
class AnnouncementRead
{
    /**
     * @var Announcement
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Announcement", inversedBy="announcementRead")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $announcement;

    /**
     * @var User
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var \DateTime()
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct(Announcement $announcement, User $user)
    {
        $this->createdAt = new \DateTime();
        $this->announcement = $announcement;
        $this->user = $user;
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
     * @return Announcement
     */
    public function getAnnouncement()
    {
        return $this->announcement;
    }

    /**
     * @param Announcement $announcement
     * @return $this
     */
    public function setAnnouncement($announcement)
    {
        $this->announcement = $announcement;

        return $this;
    }
}

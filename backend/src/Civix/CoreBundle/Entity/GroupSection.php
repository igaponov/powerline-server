<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * GroupSection.
 *
 * @ORM\Table(name="group_sections")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\GroupSectionRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class GroupSection implements GroupContentInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups", "group-section"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups", "group-section"})
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups"})
     * @ORM\ManyToMany(targetEntity="User",  inversedBy="groupSections", fetch="EXTRA_LAZY")
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="groupSections")
     * @ORM\JoinColumn(name="group_id", onDelete="CASCADE", nullable=false)
     */
    private $group;

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
     * Set title.
     *
     * @param string $title
     *
     * @return GroupSection
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * Add users.
     *
     * @param User $users
     *
     * @return GroupSection
     */
    public function addUser(User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users.
     *
     * @param User $users
     */
    public function removeUser(User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set group.
     *
     * @param Group $group
     *
     * @return GroupSection
     */
    public function setGroup(Group $group = null)
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

    public function __toString()
    {
        return $this->getTitle();
    }
}

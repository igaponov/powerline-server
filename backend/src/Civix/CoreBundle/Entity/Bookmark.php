<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bookmark entity
 *
 * @author Habibillah <habibillah@gmail.com>
 * @ORM\Table(name="bookmarks")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\BookmarkRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Bookmark
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-bookmarks"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @var Activity
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Activity")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Type("Civix\CoreBundle\Entity\Activity")
     * @Serializer\Groups({"api-bookmarks"})
     * @Serializer\SerializedName("detail")
     */
    private $item;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-bookmarks"})
     */
    private $type;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-bookmarks"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    private $createdAt;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Bookmark
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Bookmark
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set item
     *
     * @param Activity $item
     * @return Bookmark
     */
    public function setItem(Activity $item)
    {
        $this->item = $item;
    
        return $this;
    }

    /**
     * Get item
     *
     * @return Activity
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Bookmark
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     * @deprecated For compatibility with old API version
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("integer")
     * @Serializer\Groups({"api-bookmarks"})
     */
    public function getItemId()
    {
        return $this->getItem()->getId();
    }
}
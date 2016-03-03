<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bookmark entity
 *
 * @ORM\Table(name="bookmarks")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\BookmarkRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Bookmark
{
    const TYPE_PETITION = "petition";
    const TYPE_PETITION_COMMENT = "petition_comment";
    const TYPE_PETITION_ANSWER = "petition_answer";

    const TYPE_POLL = "poll";
    const TYPE_POLL_COMMENT = "poll_comment";
    const TYPE_POLL_ANSWER = "poll_answer";

    const TYPE_POST = "post";

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
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-bookmarks"})
     */
    private $itemId;

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
     * Set itemId
     *
     * @param integer $itemId
     * @return Bookmark
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    
        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->itemId;
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
}
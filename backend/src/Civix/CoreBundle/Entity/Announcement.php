<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Model\Group\GroupSectionTrait;
use Civix\CoreBundle\Parser\UrlConverter;
use Civix\CoreBundle\Serializer\Type\Image;
use Civix\CoreBundle\Validator\Constraints\PublishDate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Announcement.
 *
 * @ORM\Table(name="announcements")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\AnnouncementRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(name="groupSections",
 *         joinTable=@ORM\JoinTable(name="announcement_sections",
 *             inverseJoinColumns={@ORM\JoinColumn(name="group_section_id")}
 *         )
 *     )
 * })
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "group" = "Civix\CoreBundle\Entity\Announcement\GroupAnnouncement",
 *      "representative" = "Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement",
 * })
 * @Assert\Callback(methods={"isContentValid"})
 * @Serializer\ExclusionPolicy("all")
 * @PublishDate(objectName="Announcement", groups={"update", "publish"})
 */
abstract class Announcement implements LeaderContentInterface
{
    use GroupSectionTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     * @Assert\NotBlank()
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="content_parsed", type="text")
     * @Assert\NotBlank(message="The announcement should not be blank", groups={"Default", "update"})
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api"})
     */
    private $contentParsed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="published_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    private $publishedAt;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Representative")
     * @ORM\JoinColumn(name="representative_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $representative;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api"})
     * @Serializer\Type("Civix\CoreBundle\Entity\Group")
     */
    protected $group;

    public function __construct()
    {
        $this->groupSections = new ArrayCollection();
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
     * Set content.
     *
     * @param string $content
     *
     * @return Announcement
     */
    public function setContent($content)
    {
        $this->content = $content;
        $this->setContentParsed(UrlConverter::convert($content));

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set contentParsed.
     *
     * @param string $contentParsed
     *
     * @return Announcement
     */
    public function setContentParsed($contentParsed)
    {
        $this->contentParsed = $contentParsed;

        return $this;
    }

    /**
     * Get contentParsed.
     *
     * @return string
     */
    public function getContentParsed()
    {
        return $this->contentParsed;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Announcement
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set publishedAt.
     *
     * @param \DateTime $publishedAt
     *
     * @return Announcement
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Get publishedAt.
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedDate()
    {
        $this->setCreatedAt(new \DateTime());
    }

    public function isContentValid(ExecutionContextInterface $context)
    {
        $text = preg_replace(array('/<a[^>]+href[^>]+>/', '/<\/a>/'), '', $this->contentParsed);

        if (mb_strlen($text, 'utf-8') > 250) {
            $context->addViolationAt('content', 'The message too long');
        }
    }

    /**
     * @Serializer\Groups({"api"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("share_picture")
     * @Serializer\Type("Image")
     */
    public function getSharePicture()
    {
        $entity = $this->getUser();
        if ($entity instanceof Representative && !$entity->getAvatar()) {
            $entity = $entity->getRepresentativeStorage();
        }

        return new Image($entity, 'avatar');
    }

    public function getGroup()
    {
        return $this->getUser();
    }

    /**
     * @return LeaderInterface
     * 
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("user")
     * @Serializer\Groups({"api"})
     */
    abstract public function getUser();
    abstract public function setUser();
}

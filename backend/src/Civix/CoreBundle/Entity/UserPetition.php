<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\UserPetition\Signature;
use Civix\CoreBundle\Serializer\Type\Image;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\UserPetitionRepository")
 * @ORM\Table(name="user_petitions")
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(name="hashTags",
 *          joinTable=@ORM\JoinTable(name="hash_tags_petitions",
 *              joinColumns={@ORM\JoinColumn(name="petition_id", referencedColumnName="id", onDelete="CASCADE")},
 *              inverseJoinColumns={@ORM\JoinColumn(name="hash_tag_id", referencedColumnName="id", onDelete="CASCADE")}
 *          )
 *      )
 * })
 * @Serializer\ExclusionPolicy("all")
 */
class UserPetition implements HtmlBodyInterface
{
    use HashTaggableTrait, MetadataTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Expose()
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups={"Default", "create", "update"})
     * @Serializer\Expose()
     */
    private $body;

    /**
     * @ORM\Column(name="html_body", type="text")
     * @Serializer\Expose()
     */
    private $htmlBody;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $group;

    /**
     * @ORM\Column(name="outsiders_sign", type="boolean", options={"default" = false})
     * @Assert\Type(type="boolean")
     * @Serializer\Expose()
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("is_outsiders_sign")
     */
    private $outsidersSign = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Serializer\Expose()
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", options={"default" = false})
     * @Serializer\Expose()
     *
     * @var bool
     */
    private $boosted = false;

    /**
     * @ORM\Column(name="organization_needed", type="boolean", options={"default" = false})
     * @Serializer\Expose()
     *
     * @var bool
     */
    private $organizationNeeded = false;

    /**
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\UserPetition\Signature", mappedBy="petition", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-petitions-answers"})
     */
    private $signatures;

    /**
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\UserPetition\Comment", mappedBy="petition", cascade={"remove","persist"}, fetch="EXTRA_LAZY")
     */
    private $comments;

    /**
     * @var ArrayCollection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Civix\CoreBundle\Entity\User", cascade={"persist"}, orphanRemoval=true, mappedBy="petitionSubscriptions")
     * @ORM\JoinTable(name="petition_subscribers", joinColumns={@ORM\JoinColumn(name="petition_id", referencedColumnName="id")})
     */
    private $subscribers;

    public function __construct()
    {
        $this->signatures = new ArrayCollection();
        $this->hashTags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->metadata = new Metadata();
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
     * Set title.
     *
     * @param string $title
     *
     * @return UserPetition
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
     * Set petitionBody.
     *
     * @param string $body
     *
     * @return UserPetition
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get petitionBody.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return mixed
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * @param mixed $htmlBody
     * @return UserPetition
     */
    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    /**
     * Set isOutsidersSign.
     *
     * @param bool $outsidersSign
     *
     * @return UserPetition
     */
    public function setOutsidersSign($outsidersSign)
    {
        $this->outsidersSign = $outsidersSign;

        return $this;
    }

    /**
     * Get isOutsidersSign.
     *
     * @return bool
     */
    public function isOutsidersSign()
    {
        return $this->outsidersSign;
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
     * Set group.
     *
     * @param Group $group
     *
     * @return UserPetition
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

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return UserPetition
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

    public function boost()
    {
        $this->boosted = true;

        return $this;
    }

    public function isBoosted()
    {
        return $this->boosted;
    }

    /**
     * @return bool
     */
    public function isOrganizationNeeded()
    {
        return $this->organizationNeeded;
    }

    /**
     * @param bool $organizationNeeded
     *
     * @return UserPetition
     */
    public function setOrganizationNeeded($organizationNeeded)
    {
        $this->organizationNeeded = $organizationNeeded;

        return $this;
    }

    /**
     * @Serializer\Groups({"api-petitions-list"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("quorum_count")
     */
    public function getQuorumCount()
    {
        $currentPercent = $this->getGroup()->getPetitionPercent();
        if (empty($currentPercent)) {
            $currentPercent = PetitionManager::PERCENT_IN_GROUP;
        }

        return round((
                $this->getGroup()->getUsers()->count() * $currentPercent) / 100
        );
    }

    /**
     * Add signature.
     *
     * @param \Civix\CoreBundle\Entity\UserPetition\Signature $signature
     *
     * @return UserPetition
     */
    public function addSignature(UserPetition\Signature $signature)
    {
        $this->signatures[] = $signature;
        $signature->setPetition($this);

        return $this;
    }

    /**
     * Remove signature.
     *
     * @param \Civix\CoreBundle\Entity\UserPetition\Signature $signature
     */
    public function removeSignature(UserPetition\Signature $signature)
    {
        $this->signatures->removeElement($signature);
    }

    /**
     * @return ArrayCollection|Signature[]
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * @param User $user
     * @return Signature
     */
    public function sign(User $user)
    {
        $signature = new Signature();
        $signature->setUser($user);
        $this->addSignature($signature);

        return $signature;
    }

    /**
     * @return int
     */
    public function getResponsesCount()
    {
        return $this->getSignatures()->count();
    }

    /**
     * Set cachedHashTags.
     *
     * @param array $cachedHashTags
     *
     * @return UserPetition
     */
    public function setCachedHashTags($cachedHashTags)
    {
        $this->cachedHashTags = $cachedHashTags;

        return $this;
    }

    /**
     * Get cachedHashTags.
     *
     * @return array
     */
    public function getCachedHashTags()
    {
        return $this->cachedHashTags;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("share_picture")
     * @Serializer\Type("Image")
     */
    public function getSharePicture()
    {
        $entity = $this->isBoosted() ? $this->getGroup() : $this->getUser();

        return new Image($entity, 'avatar');
    }

    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add subscribers
     *
     * @param User $subscribers
     * @return UserPetition
     */
    public function addSubscriber(User $subscribers)
    {
        $this->subscribers[] = $subscribers;

        return $this;
    }

    /**
     * Remove subscribers
     *
     * @param User $subscribers
     */
    public function removeSubscriber(User $subscribers)
    {
        $this->subscribers->removeElement($subscribers);
    }

    /**
     * Get subscribers
     *
     * @return Collection|User[]
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("petition_body_html")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getPetitionBodyHtml()
    {
        return $this->htmlBody;
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("petition_body")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getPetitionBody()
    {
        return $this->body;
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("expire_at")
     * @Serializer\Type("DateTime")
     *
     * @internal
     */
    public function getExpireAt()
    {
        return new \DateTime('+1 year');
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("user_expire_interval")
     * @Serializer\Type("integer")
     *
     * @internal
     */
    public function getUserExpireInterval()
    {
        return 0;
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("type")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getType()
    {
        return 'long petition';
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("link")
     * @Serializer\Type("string")
     *
     * @internal
     */
    public function getLink()
    {
        return '';
    }

    /**
     * Virtual property for old endpoint
     *
     * @return mixed
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("publish_status")
     * @Serializer\Type("integer")
     *
     * @internal
     */
    public function getPublishStatus()
    {
        return (int)$this->boosted;
    }

    /**
     * @internal
     * @return ArrayCollection
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("array<Civix\CoreBundle\Entity\UserPetition\Signature>")
     * @Serializer\Groups({"api-petitions-answers"})
     */
    public function getAnswers()
    {
        return $this->signatures;
    }
}
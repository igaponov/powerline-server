<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\UserPetition\Comment;
use Civix\CoreBundle\Entity\UserPetition\Signature;
use Civix\CoreBundle\Serializer\Type\Image;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use DateTime;
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
 *      ),
 *      @ORM\AssociationOverride(name="spamMarks",
 *          joinTable=@ORM\JoinTable(name="spam_user_petitions")
 *      )
 * })
 * @Serializer\ExclusionPolicy("all")
 */
class UserPetition implements HtmlBodyInterface, SubscriptionInterface, CommentedInterface, HashTaggableInterface
{
    use HashTaggableTrait, MetadataTrait, SpamMarksTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "petition"})
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "petition"})
     */
    private $title = '';

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups={"Default", "create", "update"})
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "petition"})
     */
    private $body = '';

    /**
     * @ORM\Column(name="html_body", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "petition"})
     */
    private $htmlBody = '';

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Serializer\Expose()
     */
    private $group;

    /**
     * @ORM\Column(name="outsiders_sign", type="boolean", options={"default" = false})
     * @Assert\Type(type="boolean")
     * @Serializer\Expose()
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("is_outsiders_sign")
     * @Serializer\Groups({"Default", "petition"})
     */
    private $outsidersSign = false;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     * @Serializer\Groups({"Default", "petition"})
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Serializer\Expose()
     */
    private $user;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = false})
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "petition"})
     */
    private $boosted = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="organization_needed", type="boolean", options={"default" = false})
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "petition"})
     */
    private $organizationNeeded = false;

    /**
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\UserPetition\Signature", mappedBy="petition", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-petitions-answers", "activity-list"})
     */
    private $signatures;

    /**
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\UserPetition\Comment", mappedBy="petition", cascade={"remove","persist"}, fetch="EXTRA_LAZY")
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"activity-list"})
     * @Serializer\Since("2")
     */
    private $comments;

    /**
     * @var ArrayCollection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Civix\CoreBundle\Entity\User", cascade={"persist"}, mappedBy="petitionSubscriptions", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="petition_subscribers", joinColumns={@ORM\JoinColumn(name="petition_id", referencedColumnName="id")})
     */
    private $subscribers;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = false})
     * @Serializer\Expose()
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"Default", "petition"})
     */
    private $supportersWereInvited = false;

    /**
     * @var bool
     *
     * @ORM\Column("automatic_boost", type="boolean", options={"default" = true}, nullable=false)
     * @Serializer\Expose()
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"Default", "petition"})
     */
    private $automaticBoost = true;

    /**
     * @var File
     *
     * @ORM\Embedded(class="Civix\CoreBundle\Entity\File", columnPrefix="")
     */
    private $facebookThumbnail;

    public function __construct()
    {
        $this->signatures = new ArrayCollection();
        $this->hashTags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->metadata = new Metadata();
        $this->subscribers = new ArrayCollection();
        $this->facebookThumbnail = new File();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
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
    public function setTitle(string $title): UserPetition
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): string
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
    public function setBody(string $body): UserPetition
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get petitionBody.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return mixed
     */
    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    /**
     * @param mixed $htmlBody
     * @return UserPetition
     */
    public function setHtmlBody(string $htmlBody): UserPetition
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
    public function setOutsidersSign(bool $outsidersSign): UserPetition
    {
        $this->outsidersSign = $outsidersSign;

        return $this;
    }

    /**
     * Get isOutsidersSign.
     *
     * @return bool
     */
    public function isOutsidersSign(): bool
    {
        return $this->outsidersSign;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
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
    public function setGroup(Group $group): UserPetition
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return Group
     */
    public function getGroup(): ?Group
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
    public function setUser(User $user): UserPetition
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function boost(): UserPetition
    {
        $this->boosted = true;

        return $this;
    }

    public function isBoosted(): bool
    {
        return $this->boosted;
    }

    /**
     * @return bool
     */
    public function isOrganizationNeeded(): bool
    {
        return $this->organizationNeeded;
    }

    /**
     * @param bool $organizationNeeded
     *
     * @return UserPetition
     */
    public function setOrganizationNeeded(bool $organizationNeeded): UserPetition
    {
        $this->organizationNeeded = $organizationNeeded;

        return $this;
    }

    /**
     * @Serializer\Groups({"api-petitions-list"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("quorum_count")
     */
    public function getQuorumCount(): float
    {
        $group = $this->getGroup();
        $currentPercent = $group ? $group->getPetitionPercent() : null;
        if (!$currentPercent) {
            $currentPercent = PetitionManager::PERCENT_IN_GROUP;
        }

        return $group ? round(($group->getUsers()->count() * $currentPercent) / 100) : 0;
    }

    /**
     * Add signature.
     *
     * @param \Civix\CoreBundle\Entity\UserPetition\Signature $signature
     *
     * @return UserPetition
     */
    public function addSignature(UserPetition\Signature $signature): UserPetition
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
    public function removeSignature(UserPetition\Signature $signature): void
    {
        $this->signatures->removeElement($signature);
    }

    /**
     * @return Collection|Signature[]
     */
    public function getSignatures(): Collection
    {
        return $this->signatures;
    }

    /**
     * @param User $user
     * @return Signature
     */
    public function sign(User $user): UserPetition\Signature
    {
        $signature = new Signature();
        $signature->setUser($user);
        $this->addSignature($signature);

        return $signature;
    }

    /**
     * @return int
     */
    public function getResponsesCount(): int
    {
        return $this->getSignatures()->count();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("share_picture")
     * @Serializer\Type("Image")
     */
    public function getSharePicture(): Image
    {
        $entity = $this->isBoosted() ? $this->getGroup() : $this->getUser();

        return new Image($entity, 'avatar');
    }

    /**
     * Add comment
     *
     * @param BaseComment|Comment $comment
     * @return $this
     */
    public function addComment(BaseComment $comment): UserPetition
    {
        $this->comments[] = $comment;
        $comment->setPetition($this);

        return $this;
    }

    /**
     * Remove comment
     *
     * @param BaseComment $comment
     */
    public function removeComment(BaseComment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Add subscribers
     *
     * @param User $subscriber
     * @return UserPetition
     */
    public function addSubscriber(User $subscriber): UserPetition
    {
        $this->subscribers[] = $subscriber;

        return $this;
    }

    /**
     * Remove subscribers
     *
     * @param User $subscriber
     */
    public function removeSubscriber(User $subscriber): void
    {
        $this->subscribers->removeElement($subscriber);
    }

    /**
     * Get subscribers
     *
     * @return Collection|User[]
     */
    public function getSubscribers(): Collection
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
    public function getPetitionBodyHtml(): string
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
    public function getPetitionBody(): string
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
    public function getExpireAt(): DateTime
    {
        return new DateTime('+1 year');
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
    public function getUserExpireInterval(): int
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
    public function getType(): string
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
    public function getLink(): string
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
    public function getPublishStatus(): int
    {
        return (int)$this->boosted;
    }

    /**
     * @internal
     * @return Collection
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("array<Civix\CoreBundle\Entity\UserPetition\Signature>")
     * @Serializer\Groups({"api-petitions-answers"})
     */
    public function getAnswers(): Collection
    {
        return $this->signatures;
    }

    /**
     * @return bool
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"activity-list"})
     */
    public function isSubscribed(): bool
    {
        return !$this->subscribers->isEmpty();
    }

    /**
     * @return int
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("group_id")
     * @Serializer\Type("integer")
     */
    public function getGroupId(): ?int
    {
        $group = $this->getGroup();

        return $group ? $group->getId() : null;
    }

    /**
     * @return bool
     */
    public function isSupportersWereInvited(): bool
    {
        return $this->supportersWereInvited;
    }

    /**
     * @param bool $supportersWereInvited
     * @return UserPetition
     */
    public function setSupportersWereInvited($supportersWereInvited): UserPetition
    {
        $this->supportersWereInvited = $supportersWereInvited;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutomaticBoost(): bool
    {
        return $this->automaticBoost;
    }

    /**
     * @param bool $automaticBoost
     * @return $this
     */
    public function setAutomaticBoost($automaticBoost): UserPetition
    {
        $this->automaticBoost = $automaticBoost;

        return $this;
    }

    /**
     * @return File
     */
    public function getFacebookThumbnail(): File
    {
        return $this->facebookThumbnail;
    }

    /**
     * @param File $facebookThumbnail
     * @return UserPetition
     */
    public function setFacebookThumbnail(File $facebookThumbnail): UserPetition
    {
        $this->facebookThumbnail = $facebookThumbnail;

        return $this;
    }

    /**
     * Get facebook thumbnail image
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"Default", "petition"})
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("facebook_thumbnail")
     * @return Image
     */
    public function getFacebookThumbnailImage(): Image
    {
        return new Image($this, 'facebookThumbnail.file', null, false);
    }
}
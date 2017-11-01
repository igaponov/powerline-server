<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Post\Comment;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Validator\Constraints\Property;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\PostRepository")
 * @ORM\Table(name="user_posts")
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(name="hashTags",
 *          joinTable=@ORM\JoinTable(name="hash_tags_posts")
 *      ),
 *      @ORM\AssociationOverride(name="spamMarks",
 *          joinTable=@ORM\JoinTable(name="spam_posts")
 *      )
 * })
 * @Serializer\ExclusionPolicy("all")
 */
class Post implements HtmlBodyInterface, SubscriptionInterface, CommentedInterface, HashTaggableInterface, HasMetadataInterface
{
    use HashTaggableTrait,
        HasMetadataTrait,
        SpamMarksTrait,
        PostSerializableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "api-petitions-info", "api-petitions-list", "api-leader-micropetition",
     * "post"})
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups={"Default", "create", "update"})
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "post"})
     */
    private $body = '';

    /**
     * @ORM\Column(name="html_body", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "post"})
     */
    private $htmlBody = '';

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     */
    private $group;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     * @Serializer\Groups({"Default", "post"})
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="expired_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     * @Serializer\Groups({"Default", "post"})
     */
    private $expiredAt;

    /**
     * @ORM\Column(name="user_expire_interval", type="integer")
     * @Assert\NotBlank(groups={"Default", "update"})
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "post"})
     *
     * @var int
     */
    private $userExpireInterval = 0;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", options={"default" = false})
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "post"})
     *
     * @var bool
     */
    private $boosted = false;

    /**
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\Post\Vote", mappedBy="post", cascade={"remove"}, fetch="EXTRA_LAZY")
     * @Serializer\Expose()
     * @Serializer\Groups({"post-votes", "activity-list"})
     */
    private $votes;

    /**
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\Post\Comment", mappedBy="post", cascade={"remove","persist"}, fetch="EXTRA_LAZY")
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"activity-list"})
     * @Serializer\Since("2")
     */
    private $comments;

    /**
     * @var ArrayCollection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Civix\CoreBundle\Entity\User",
     *     cascade={"persist"}, mappedBy="postSubscriptions", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="post_subscribers")
     */
    private $subscribers;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = false})
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "post"})
     * @Serializer\Type("boolean")
     */
    private $supportersWereInvited = false;

    /**
     * @var bool
     *
     * @ORM\Column("automatic_boost", type="boolean", options={"default" = true}, nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "post"})
     * @Serializer\Type("boolean")
     */
    private $automaticBoost = true;

    /**
     * @var File
     *
     * @ORM\Embedded(class="Civix\CoreBundle\Entity\File", columnPrefix="facebook_thumbnail_")
     */
    private $facebookThumbnail;

    /**
     * @var File
     *
     * @ORM\Embedded(class="Civix\CoreBundle\Entity\File", columnPrefix="")
     *
     * @Property(propertyPath="file", constraints={@Assert\File(mimeTypes={"image/jpg", "image/jpeg", "image/png"})}, groups={"Default", "create", "update"})
     */
    protected $image;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->hashTags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->metadata = new Metadata();
        $this->subscribers = new ArrayCollection();
        $this->facebookThumbnail = new File();
        $this->image = new File();
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
     * Set petitionBody.
     *
     * @param string $body
     *
     * @return Post
     */
    public function setBody(string $body): Post
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
     * @return Post
     */
    public function setHtmlBody(string $htmlBody): Post
    {
        $this->htmlBody = $htmlBody;

        return $this;
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
     * Set expireAt.
     *
     * @param DateTime $expiredAt
     *
     * @return Post
     */
    public function setExpiredAt(?DateTime $expiredAt): Post
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * Get expireAt.
     *
     * @return DateTime
     */
    public function getExpiredAt(): ?DateTime
    {
        return $this->expiredAt;
    }

    /**
     * Set group.
     *
     * @param Group $group
     *
     * @return Post
     */
    public function setGroup(Group $group): Post
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
     * @return Post
     */
    public function setUser(User $user): Post
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

    public function boost(): Post
    {
        $this->boosted = true;

        return $this;
    }

    public function isBoosted(): bool
    {
        return $this->boosted;
    }

    public function getUserExpireInterval(): int
    {
        return $this->userExpireInterval;
    }

    public function setUserExpireInterval(int $interval): Post
    {
        $this->userExpireInterval = $interval;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-petitions-list"})
     * @Serializer\SerializedName("quorum_count")
     * @Serializer\Type("float")
     */
    public function getQuorumCount(): float
    {
        $group = $this->getGroup();
        $currentPercent = $group ? $group->getPetitionPercent() : null;
        if (!$currentPercent) {
            $currentPercent = PetitionManager::PERCENT_IN_GROUP;
        }

        return round(($group->getUserGroups()->count() * $currentPercent) / 100);
    }

    /**
     * Add vote.
     *
     * @param Vote $vote
     *
     * @return Post
     */
    public function addVote(Vote $vote): Post
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     *
     * @param Vote $vote
     */
    public function removeVote(Vote $vote): void
    {
        $this->votes->removeElement($vote);
    }

    /**
     * @return Collection|Vote[]
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    /**
     * @return int
     */
    public function getResponsesCount(): int
    {
        return $this->getVotes()->count();
    }

    /**
     * Add comment
     *
     * @param BaseComment|Comment $comment
     * @return $this
     */
    public function addComment(BaseComment $comment): Post
    {
        $this->comments[] = $comment;
        $comment->setPost($this);

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
     * @param User $subscribers
     * @return Post
     */
    public function addSubscriber(User $subscribers): Post
    {
        $this->subscribers[] = $subscribers;

        return $this;
    }

    /**
     * Remove subscribers
     *
     * @param User $subscribers
     */
    public function removeSubscriber(User $subscribers): void
    {
        $this->subscribers->removeElement($subscribers);
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
     * @return Collection|Vote[]
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"post-votes"})
     * @Serializer\Type("array<Civix\CoreBundle\Entity\Post\Vote>")
     */
    public function getAnswers(): Collection
    {
        return $this->votes;
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
     * @return bool
     */
    public function isSupportersWereInvited(): bool
    {
        return $this->supportersWereInvited;
    }

    /**
     * @param bool $supportersWereInvited
     * @return Post
     */
    public function setSupportersWereInvited(bool $supportersWereInvited): Post
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
    public function setAutomaticBoost(bool $automaticBoost)
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
     * @return Post
     */
    public function setFacebookThumbnail(File $facebookThumbnail): Post
    {
        $this->facebookThumbnail = $facebookThumbnail;

        return $this;
    }

    /**
     * @return File
     */
    public function getImage(): File
    {
        return $this->image;
    }

    /**
     * @param File $image
     * @return Post
     */
    public function setImage(?File $image): Post
    {
        if ($image) {
            $this->image = $image;
        }

        return $this;
    }
}
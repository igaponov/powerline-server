<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Post\Comment;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Serializer\Type\Image;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
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
class Post implements HtmlBodyInterface, SubscriptionInterface, CommentedInterface, HashTaggableInterface, UserMentionableInterface
{
    use HashTaggableTrait, MetadataTrait, UserMentionableTrait, SpamMarksTrait;

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
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable()
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     * @Serializer\Groups({"Default", "post"})
     */
    private $createdAt;

    /**
     * @var \DateTime
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
    private $userExpireInterval;

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

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->hashTags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->metadata = new Metadata();
        $this->subscribers = new ArrayCollection();
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
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set expireAt.
     *
     * @param \DateTime $expiredAt
     *
     * @return Post
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * Get expireAt.
     *
     * @return \DateTime
     */
    public function getExpiredAt()
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
     * @return Post
     */
    public function setUser(User $user = null)
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

    public function getUserExpireInterval()
    {
        return $this->userExpireInterval;
    }

    public function setUserExpireInterval($interval)
    {
        $this->userExpireInterval = $interval;

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
                $this->getGroup()->getUserGroups()->count() * $currentPercent) / 100
        );
    }

    /**
     * Add vote.
     *
     * @param Vote $vote
     *
     * @return Post
     */
    public function addVote(Vote $vote)
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     *
     * @param Vote $vote
     */
    public function removeVote(Vote $vote)
    {
        $this->votes->removeElement($vote);
    }

    /**
     * @return ArrayCollection|Vote[]
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @return int
     */
    public function getResponsesCount()
    {
        return $this->getVotes()->count();
    }

    /**
     * @Serializer\Groups({"api-petitions-info"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("share_picture")
     * @Serializer\Type("Image")
     */
    public function getSharePicture()
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
    public function addComment(BaseComment $comment)
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
    public function removeComment(BaseComment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return Collection|Comment[]
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add subscribers
     *
     * @param User $subscribers
     * @return Post
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
     * @return ArrayCollection
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"post-votes"})
     */
    public function getAnswers()
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
    public function isSubscribed()
    {
        return (bool)$this->subscribers->count();
    }

    /**
     * @return bool
     */
    public function isSupportersWereInvited()
    {
        return $this->supportersWereInvited;
    }

    /**
     * @param bool $supportersWereInvited
     * @return Post
     */
    public function setSupportersWereInvited($supportersWereInvited)
    {
        $this->supportersWereInvited = $supportersWereInvited;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutomaticBoost()
    {
        return $this->automaticBoost;
    }

    /**
     * @param bool $automaticBoost
     * @return $this
     */
    public function setAutomaticBoost($automaticBoost)
    {
        $this->automaticBoost = $automaticBoost;

        return $this;
    }
}
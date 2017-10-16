<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Serializer\Type\GroupOwnerData;
use Civix\CoreBundle\Serializer\Type\RepresentativeOwnerData;
use Civix\CoreBundle\Serializer\Type\UserOwnerData;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Serializer\Type\OwnerData;
use Civix\CoreBundle\Serializer\Type\Image;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\ActivityRepository")
 * @ORM\Table(name="activities", indexes={@ORM\Index(name="sent_at_idx", columns={"sent_at"})})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "question"  = "Civix\CoreBundle\Entity\Activities\Question",
 *      "user-petition" = "Civix\CoreBundle\Entity\Activities\UserPetition",
 *      "post" = "Civix\CoreBundle\Entity\Activities\Post",
 *      "petition" = "Civix\CoreBundle\Entity\Activities\Petition",
 *      "leader-news" = "Civix\CoreBundle\Entity\Activities\LeaderNews",
 *      "payment-request" = "Civix\CoreBundle\Entity\Activities\PaymentRequest",
 *      "crowdfunding-payment-request" = "Civix\CoreBundle\Entity\Activities\CrowdfundingPaymentRequest",
 *      "leader-event" = "Civix\CoreBundle\Entity\Activities\LeaderEvent"
 * })
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @Serializer\ExclusionPolicy("all")
 * @Vich\Uploadable
 */
abstract class Activity implements HtmlBodyInterface
{
    const TYPE_QUESTION = 'question';
    const TYPE_PETITION = 'petition';
    const TYPE_USER_PETITION = 'user-petition';
    const TYPE_POST = 'post';
    const TYPE_LEADER_NEWS = 'leader-news';
    const TYPE_PAYMENT_REQUEST = 'payment-request';
    const TYPE_CROWDFUNDING_PAYMENT_REQUEST = 'crowdfunding-payment-request';
    const TYPE_LEADER_EVENT = 'leader-event';
    const TYPE_ALL = 'all';

    const ZONE_PRIORITIZED = 0;

    const ZONE_NON_PRIORITIZED = 1;

    const ZONE_EXPIRED = 2;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    protected $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     *
     * @var string
     */
    protected $title = '';

    /**
     * @ORM\Column(name="description", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     *
     * @var string
     */
    protected $description = '';

    /**
     * @ORM\Column(name="description_html", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     *
     * @var string
     */
    protected $descriptionHtml = '';

    /**
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     *
     * @var DateTime
     */
    protected $sentAt;

    /**
     * @ORM\Column(name="expire_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     *
     * @var DateTime
     */
    protected $expireAt;

    /**
     * @ORM\Column(name="responses_count", type="integer", options={"default" = 0})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     *
     * @var int
     */
    protected $responsesCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="upvotes_count", type="integer", options={"default" = 0})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    protected $upvotesCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="downvotes_count", type="integer", options={"default" = 0})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    protected $downvotesCount = 0;

    /**
     * @var array
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("OwnerData")
     * @Serializer\Accessor(getter="getOwnerData")
     *
     * @ORM\Column(name="owner", type="array")
     */
    protected $owner;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\UserRepresentative")
     * @ORM\JoinColumn(name="representative_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $representative;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\Superuser")
     * @ORM\JoinColumn(name="superuser_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $superuser;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id",  referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    protected $user;

    /**
     * @var ArrayCollection|ActivityCondition[]
     *
     * @ORM\OneToMany(targetEntity="ActivityCondition", mappedBy="activity", cascade={"persist"})
     */
    protected $activityConditions;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Accessor(getter="getEntity")
     */
    protected $entity;

    /**
     * @ORM\Column(name="is_outsiders", type="boolean", nullable=true)
     *
     * @var bool
     */
    protected $isOutsiders;

    /**
     * @var bool
     * @Serializer\Expose()
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"api-activities"})
     */
    protected $read = false;

    /**
     * @ORM\Column(name="rate_up", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $rateUp;

    /**
     * @ORM\Column(name="rate_down", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $rateDown;

    /**
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    protected $imageSrc;

    /**
     * @Vich\UploadableField(mapping="educational_image", fileNameProperty="imageSrc")
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("image_src")
     * @Serializer\Accessor(getter="getActivityImage")
     */
    protected $image;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $updatedAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var ArrayCollection|ActivityRead[]
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\ActivityRead", mappedBy="activity", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    private $activityRead;

    /**
     * @var Question
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Poll\Question")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"activity-list"})
     * @Serializer\Type("Civix\CoreBundle\Entity\Poll\Question")
     * @Serializer\SerializedName("poll")
     */
    private $question;

    /**
     * @var UserPetition
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\UserPetition")
     * @ORM\JoinColumn(onDelete="CASCADE", unique=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"activity-list"})
     * @Serializer\SerializedName("user_petition")
     */
    protected $petition;

    /**
     * @var Post
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Post")
     * @ORM\JoinColumn(onDelete="CASCADE", unique=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"activity-list"})
     */
    protected $post;

    /**
     * @var int
     */
    protected $zone;

    public static function getZones(): array
    {
        return [
            self::ZONE_PRIORITIZED => 'prioritized',
            self::ZONE_NON_PRIORITIZED => 'non_prioritized',
            self::ZONE_EXPIRED => 'expired',
        ];
    }

    public function __construct()
    {
        $this->activityConditions = new ArrayCollection();
        $this->activityRead = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Activity
     */
    public function setTitle($title): Activity
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Activity
     */
    public function setDescription($description): Activity
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getDescriptionHtml(): ?string
    {
        return $this->descriptionHtml;
    }

    /**
     * @param string $descriptionHtml
     * @return Activity
     */
    public function setDescriptionHtml($descriptionHtml): Activity
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Set sentAt.
     *
     * @param DateTime $sentAt
     *
     * @return Activity
     */
    public function setSentAt(?DateTime $sentAt): Activity
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get sentAt.
     *
     * @return DateTime
     */
    public function getSentAt(): ?DateTime
    {
        return $this->sentAt;
    }

    /**
     * Set expireAt.
     *
     * @param DateTime $expireAt
     *
     * @return Activity
     */
    public function setExpireAt(?DateTime $expireAt): Activity
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * Get expireAt.
     *
     * @return DateTime
     */
    public function getExpireAt(): ?DateTime
    {
        return $this->expireAt;
    }

    /**
     * Set responses_count.
     *
     * @param int $responsesCount
     *
     * @return Activity
     */
    public function setResponsesCount($responsesCount): Activity
    {
        $this->responsesCount = $responsesCount;

        return $this;
    }

    /**
     * Get responses_count.
     *
     * @return int
     */
    public function getResponsesCount(): int
    {
        return $this->responsesCount;
    }

    /**
     * @return int
     */
    public function getUpvotesCount(): int
    {
        return $this->upvotesCount;
    }

    /**
     * @param int $upvotesCount
     * @return Activity
     */
    public function setUpvotesCount(int $upvotesCount): Activity
    {
        $this->upvotesCount = $upvotesCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getDownvotesCount(): int
    {
        return $this->downvotesCount;
    }

    /**
     * @param int $downvotesCount
     * @return Activity
     */
    public function setDownvotesCount(int $downvotesCount): Activity
    {
        $this->downvotesCount = $downvotesCount;

        return $this;
    }

    public function setRepresentative(UserRepresentative $representative): Activity
    {
        $this->representative = $representative;
        $this->owner = self::toRepresentativeOwnerData($representative);

        return $this;
    }

    public function setGroup(Group $group): Activity
    {
        $this->group = $group;
        $this->owner = self::toGroupOwnerData($group);

        return $this;
    }

    public function setSuperuser(Superuser $superuser): Activity
    {
        $this->superuser = $superuser;
        $this->owner = [
            'type' => 'admin',
            'official_title' => 'The Global Forum',
        ];

        return $this;
    }

    public function setUser(User $user): Activity
    {
        $this->user = $user;
        $this->owner = self::toUserOwnerData($user);

        return $this;
    }

    public static function toRepresentativeOwnerData(UserRepresentative $userRepresentative): array
    {
        $data = [
            'id' => $userRepresentative->getId(),
            'type' => $userRepresentative->getType(),
            'official_title' => $userRepresentative->getOfficialTitle(),
            'first_name' => $userRepresentative->getUser()->getFirstName(),
            'last_name' => $userRepresentative->getUser()->getLastName(),
            'avatar_file_path' => $userRepresentative->getRepresentative() ? $userRepresentative->getRepresentative()->getAvatarFileName() : $userRepresentative->getAvatarFileName(),
        ];
        if ($userRepresentative->getRepresentative()) {
            $data['cicero_id'] = $userRepresentative->getRepresentative();
        }

        return $data;
    }

    public static function toGroupOwnerData(Group $group): array
    {
        return [
            'id' => $group->getId(),
            'type' => $group->getType(),
            'group_type' => $group->getGroupType(),
            'official_name' => $group->getOfficialName(),
            'avatar_file_path' => $group->getAvatarFileName(),
        ];
    }

    public static function toUserOwnerData(User $user): array
    {
        return [
            'id' => $user->getId(),
            'type' => $user->getType(),
            'official_title' => '',
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'avatar_file_path' => $user->getAvatarFileName(),
        ];
    }

    public function getOwner(): array
    {
        return $this->owner;
    }

    public function setOwner(array $data): Activity
    {
        $this->owner = $data;

        return $this;
    }

    public function getOwnerData(): OwnerData
    {
        switch ($this->owner['type'] ?? '') {
            case 'user':
                return new UserOwnerData($this->owner);
            case 'group':
                return new GroupOwnerData($this->owner);
            case 'representative':
                return new RepresentativeOwnerData($this->owner);
            default:
                return new OwnerData($this->owner);
        }
    }

    /**
     * Set isOutsiders.
     *
     * @param bool $isOutsiders
     *
     * @return Activity
     */
    public function setIsOutsiders(?bool $isOutsiders): Activity
    {
        $this->isOutsiders = $isOutsiders;

        return $this;
    }

    /**
     * Get isOutsiders.
     *
     * @return bool
     */
    public function getIsOutsiders(): ?bool
    {
        return $this->isOutsiders;
    }

    abstract public function getEntity();

    /**
     * Get representative.
     *
     * @return \Civix\CoreBundle\Entity\UserRepresentative
     */
    public function getRepresentative(): ?UserRepresentative
    {
        return $this->representative;
    }

    /**
     * Get group.
     *
     * @return \Civix\CoreBundle\Entity\Group
     */
    public function getGroup(): ?Group
    {
        return $this->group;
    }

    /**
     * Get superuser.
     *
     * @return \Civix\CoreBundle\Entity\Superuser
     */
    public function getSuperuser(): ?Superuser
    {
        return $this->superuser;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Add activityConditions.
     *
     * @param ActivityCondition $activityConditions
     *
     * @return Activity
     */
    public function addActivityCondition(ActivityCondition $activityConditions): Activity
    {
        $this->activityConditions[] = $activityConditions;
        $activityConditions->setActivity($this);

        return $this;
    }

    /**
     * Remove activityConditions.
     *
     * @param ActivityCondition $activityConditions
     */
    public function removeActivityCondition(ActivityCondition $activityConditions): void
    {
        $this->activityConditions->removeElement($activityConditions);
    }

    /**
     * Get activityConditions.
     *
     * @return Collection
     */
    public function getActivityConditions(): Collection
    {
        return $this->activityConditions;
    }

    /**
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read;
    }

    /**
     * @param bool $read
     *
     * @return $this
     */
    public function setRead(bool $read)
    {
        $this->read = $read;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRateUp(): int
    {
        return $this->rateUp;
    }

    /**
     * @param mixed $rateUp
     *
     * @return $this
     */
    public function setRateUp(int $rateUp)
    {
        $this->rateUp = $rateUp;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateDown(): int
    {
        return $this->rateDown;
    }

    /**
     * @param int $rateDown
     *
     * @return $this
     */
    public function setRateDown(int $rateDown)
    {
        $this->rateDown = $rateDown;

        return $this;
    }

    /**
     * @return string
     */
    public function getImageSrc(): ?string
    {
        return $this->imageSrc;
    }

    /**
     * @param string $imageSrc
     *
     * @return $this
     */
    public function setImageSrc(?string $imageSrc)
    {
        $this->imageSrc = $imageSrc;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     *
     * @return $this
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function getActivityImage(): ?Image
    {
        return $this->imageSrc ? new Image($this, 'image', $this->imageSrc) : null;
    }

    public static function getActivityClassByEntity($question): string
    {
        if ($question instanceof Question\LeaderNews) {
            return Activities\LeaderNews::class;
        }
        if ($question instanceof Question\PaymentRequest && $question->getIsCrowdfunding()) {
            return Activities\CrowdfundingPaymentRequest::class;
        }
        if ($question instanceof Question\PaymentRequest) {
            return Activities\PaymentRequest::class;
        }
        if ($question instanceof Question\LeaderEvent) {
            return Activities\LeaderEvent::class;
        }
        if ($question instanceof Micropetitions\Petition) {
            return Activities\UserPetition::class;
        }
        if ($question instanceof Question\Petition) {
            return Activities\Petition::class;
        }

        return Activities\Question::class;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     * @return Activity
     */
    public function setUpdatedAt(?DateTime $updatedAt): Activity
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @param DateTime $deletedAt
     * @return Activity
     */
    public function setDeletedAt(?DateTime $deletedAt): Activity
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|ActivityRead[]
     */
    public function getActivityRead(): Collection
    {
        return $this->activityRead;
    }

    /**
     * @param ActivityRead $activityRead
     * @return Activity
     */
    public function addActivityRead(ActivityRead $activityRead): Activity
    {
        $this->activityRead[] = $activityRead;

        return $this;
    }

    /**
     * @param ActivityRead $activityRead
     */
    public function removeActivityRead(ActivityRead $activityRead): void
    {
        $this->activityRead->removeElement($activityRead);
    }

    public function isReadByUser(User $user): bool
    {
        $filter = function (ActivityRead $activityRead) use ($user) {
            return $activityRead->getUser()->getId() === $user->getId();
        };

        return $this->getActivityRead()->filter($filter)->count() > 0;
    }

    /**
     * @return Question
     */
    public function getQuestion(): ?Poll\Question
    {
        return $this->question;
    }

    /**
     * @param Question $question
     * @return Activity
     */
    public function setQuestion(?Poll\Question $question): Activity
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @return UserPetition
     */
    public function getPetition(): ?UserPetition
    {
        return $this->petition;
    }

    /**
     * @param UserPetition $petition
     * @return $this
     */
    public function setPetition(?UserPetition $petition)
    {
        $this->petition = $petition;

        return $this;
    }

    /**
     * @return int
     */
    public function getZone(): ?int
    {
        return $this->zone;
    }

    /**
     * @param int $zone
     * @return Activity
     */
    public function setZone(?int $zone): Activity
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * @return string
     *
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("zone")
     * @Serializer\Type("string")
     * @Serializer\Groups({"api-activities"})
     */
    public function getZoneLabel(): ?string
    {
        if (isset(self::getZones()[$this->zone])) {
            return self::getZones()[$this->zone];
        }

        return null;
    }

    public function getBody(): ?string
    {
        return $this->getDescription();
    }

    public function setHtmlBody(string $html): Activity
    {
        $this->setDescriptionHtml($html);

        return $this;
    }

    /**
     * @return Post
     */
    public function getPost(): ?Post
    {
        return $this->post;
    }

    /**
     * @param Post $post
     * @return Activity
     */
    public function setPost(?Post $post): Activity
    {
        $this->post = $post;

        return $this;
    }
}

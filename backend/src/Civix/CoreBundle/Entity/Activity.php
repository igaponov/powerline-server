<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Activities\Petition;
use Doctrine\Common\Collections\ArrayCollection;
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
    const TYPE_QUESTION = "question";
    const TYPE_PETITION = "petition";
    const TYPE_USER_PETITION = "user-petition";
    const TYPE_POST = "post";
    const TYPE_LEADER_NEWS = "leader-news";
    const TYPE_PAYMENT_REQUEST = "payment-request";
    const TYPE_CROWDFUNDING_PAYMENT_REQUEST = "crowdfunding-payment-request";
    const TYPE_LEADER_EVENT = "leader-event";
    const TYPE_ALL = "all";

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
    protected $title;

    /**
     * @ORM\Column(name="description", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     *
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(name="description_html", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     *
     * @var string
     */
    protected $descriptionHtml;

    /**
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     *
     * @var \DateTime()
     */
    protected $sentAt;

    /**
     * @ORM\Column(name="expire_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     *
     * @var \DateTime()
     */
    protected $expireAt;

    /**
     * @ORM\Column(name="responses_count", type="integer")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     *
     * @var int
     */
    protected $responsesCount;

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
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Representative")
     * @ORM\JoinColumn(name="representative_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $representative;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     * @Serializer\Accessor(getter="getPicture")
     */
    protected $picture;

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
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    private $updatedAt;

    /**
     * @var \DateTime
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

    public static function getZones()
    {
        return [
            self::ZONE_PRIORITIZED => 'prioritized',
            self::ZONE_NON_PRIORITIZED => 'non_prioritized',
            self::ZONE_EXPIRED => 'expired',
        ];
    }

    public function __construct()
    {
        $this->setResponsesCount(0);
        $this->activityConditions = new ArrayCollection();
        $this->activityRead = new ArrayCollection();
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
     * @return Activity
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
     * Set description.
     *
     * @param string $description
     *
     * @return Activity
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getDescriptionHtml()
    {
        return $this->descriptionHtml;
    }

    /**
     * @param string $descriptionHtml
     * @return Activity
     */
    public function setDescriptionHtml($descriptionHtml)
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Set sentAt.
     *
     * @param \DateTime $sentAt
     *
     * @return Activity
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get sentAt.
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set expireAt.
     *
     * @param \DateTime $expireAt
     *
     * @return Activity
     */
    public function setExpireAt($expireAt)
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * Get expireAt.
     *
     * @return \DateTime
     */
    public function getExpireAt()
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
    public function setResponsesCount($responsesCount)
    {
        $this->responsesCount = $responsesCount;

        return $this;
    }

    /**
     * Get responses_count.
     *
     * @return int
     */
    public function getResponsesCount()
    {
        return $this->responsesCount;
    }

    public function setRepresentative(Representative $representative)
    {
        $this->representative = $representative;
        $this->owner = self::toRepresentativeOwnerData($representative);
    }

    public function setGroup(Group $group)
    {
        $this->group = $group;
        $this->owner = self::toGroupOwnerData($group);
    }

    public function setSuperuser(Superuser $superuser)
    {
        $this->superuser = $superuser;
        $this->owner = [
            'type' => 'admin',
            'official_title' => 'The Global Forum',
        ];
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        $this->owner = self::toUserOwnerData($user);
    }

    public static function toRepresentativeOwnerData(Representative $representative)
    {
        $data = [
            'id' => $representative->getId(),
            'type' => $representative->getType(),
            'official_title' => $representative->getOfficialTitle(),
            'first_name' => $representative->getUser()->getFirstName(),
            'last_name' => $representative->getUser()->getLastName(),
            'avatar_file_path' => $representative->getCiceroRepresentative() ? $representative->getCiceroRepresentative()->getAvatarFileName() : '',
        ];
        if ($representative->getCiceroRepresentative()) {
            $data['cicero_id'] = $representative->getCiceroRepresentative();
        }

        return $data;
    }

    public static function toGroupOwnerData(Group $group)
    {
        return [
            'id' => $group->getId(),
            'type' => $group->getType(),
            'group_type' => $group->getGroupType(),
            'official_name' => $group->getOfficialName(),
            'avatar_file_path' => $group->getAvatarFileName(),
        ];
    }

    public static function toUserOwnerData(User $user)
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

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($data)
    {
        $this->owner = $data;

        return $this;
    }

    public function getOwnerData()
    {
        return new OwnerData($this->owner);
    }

    public function getPicture()
    {
        return $this->picture;
    }

    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Set isOutsiders.
     *
     * @param bool $isOutsiders
     *
     * @return Activity
     */
    public function setIsOutsiders($isOutsiders)
    {
        $this->isOutsiders = $isOutsiders;

        return $this;
    }

    /**
     * Get isOutsiders.
     *
     * @return bool
     */
    public function getIsOutsiders()
    {
        return $this->isOutsiders;
    }

    abstract public function getEntity();

    /**
     * Get representative.
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function getRepresentative()
    {
        return $this->representative;
    }

    /**
     * Get group.
     *
     * @return \Civix\CoreBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get superuser.
     *
     * @return \Civix\CoreBundle\Entity\Superuser
     */
    public function getSuperuser()
    {
        return $this->superuser;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\User
     */
    public function getUser()
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
    public function addActivityCondition(ActivityCondition $activityConditions)
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
    public function removeActivityCondition(ActivityCondition $activityConditions)
    {
        $this->activityConditions->removeElement($activityConditions);
    }

    /**
     * Get activityConditions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActivityConditions()
    {
        return $this->activityConditions;
    }

    /**
     * @return bool
     */
    public function isRead()
    {
        return $this->read;
    }

    /**
     * @param bool $read
     *
     * @return $this
     */
    public function setRead($read)
    {
        $this->read = $read;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRateUp()
    {
        return $this->rateUp;
    }

    /**
     * @param mixed $rateUp
     *
     * @return $this
     */
    public function setRateUp($rateUp)
    {
        $this->rateUp = $rateUp;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRateDown()
    {
        return $this->rateDown;
    }

    /**
     * @param int $rateDown
     *
     * @return $this
     */
    public function setRateDown($rateDown)
    {
        $this->rateDown = $rateDown;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getImageSrc()
    {
        return $this->imageSrc;
    }

    /**
     * @param mixed $imageSrc
     *
     * @return $this
     */
    public function setImageSrc($imageSrc)
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

    public function getActivityImage()
    {
        return $this->imageSrc ? new Image($this, 'image', $this->imageSrc) : null;
    }

    public static function getActivityClassByEntity($question)
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
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Activity
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime $deletedAt
     * @return Activity
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return ArrayCollection|ActivityRead[]
     */
    public function getActivityRead()
    {
        return $this->activityRead;
    }

    /**
     * @param ActivityRead $activityRead
     * @return Activity
     */
    public function addActivityRead(ActivityRead $activityRead)
    {
        $this->activityRead[] = $activityRead;

        return $this;
    }

    /**
     * @param ActivityRead $activityRead
     */
    public function removeActivityRead(ActivityRead $activityRead)
    {
        $this->activityRead->removeElement($activityRead);
    }

    public function isReadByUser(User $user)
    {
        $filter = function (ActivityRead $activityRead) use ($user) {
            return $activityRead->getUser()->getId() == $user->getId();
        };

        return !!$this->getActivityRead()->filter($filter)->count();
    }

    /**
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param Question $question
     * @return Activity
     */
    public function setQuestion($question)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @return UserPetition
     */
    public function getPetition()
    {
        return $this->petition;
    }

    /**
     * @param UserPetition $petition
     * @return $this
     */
    public function setPetition(UserPetition $petition)
    {
        $this->petition = $petition;

        return $this;
    }

    /**
     * @return int
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * @param int $zone
     * @return Activity
     */
    public function setZone($zone)
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
    public function getZoneLabel()
    {
        if (isset(self::getZones()[$this->zone])) {
            return self::getZones()[$this->zone];
        }

        return null;
    }

    public function getBody()
    {
        return $this->getDescription();
    }

    public function setHtmlBody($html)
    {
        $this->setDescriptionHtml($html);
    }

    /**
     * @return Post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @param Post $post
     * @return Activity
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }
}

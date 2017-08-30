<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Group\AdvancedAttributes;
use Civix\CoreBundle\Entity\Group\GroupField;
use Civix\CoreBundle\Entity\Group\Link;
use Civix\CoreBundle\Entity\Group\Tag;
use Civix\CoreBundle\Model\Avatar\DefaultAvatar;
use Civix\CoreBundle\Model\Avatar\DefaultAvatarInterface;
use Civix\CoreBundle\Model\Avatar\FirstLetterDefaultAvatar;
use Civix\CoreBundle\Serializer\Type\ContentRemaining;
use Civix\CoreBundle\Serializer\Type\Image;
use Civix\CoreBundle\Serializer\Type\TotalMembers;
use Civix\CoreBundle\Serializer\Type\UserRole;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Serializer\Type\Avatar;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Serializer\Type\JoinStatus;

/**
 * Group entity.
 *
 * @ORM\Table(name="groups",  indexes={
 *      @ORM\Index(name="group_officialName_ind", columns={"official_name"})
 * })
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\GroupRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"officialName"}, groups={"registration", "user-registration", "api-registration"})
 * @Serializer\ExclusionPolicy("all")
 */
class Group implements \Serializable, CheckingLimits, LeaderContentRootInterface, OfficialInterface, HasAvatarInterface, ChangeableAvatarInterface
{
    use HasStripeAccountTrait,
        HasStripeCustomerTrait,
        HasAvatarTrait,
        GroupSerializableTrait;

    const DEFAULT_AVATAR = '/bundles/civixfront/img/default_group.png';
    const DEFAULT_MAP_AVATAR = __DIR__.'/../Resources/public/img/pin-map-icon.png';

    const GROUP_TYPE_COMMON = 0;
    const GROUP_TYPE_COUNTRY = 1;
    const GROUP_TYPE_STATE = 2;
    const GROUP_TYPE_LOCAL = 3;
    const GROUP_TYPE_SPECIAL = 4;

    const GROUP_LOCATION_NAME_EUROPEAN_UNION = 'EU';
    const GROUP_LOCATION_NAME_AFRICAN_UNION = 'AFU';

    const GROUP_MEMBERSHIP_PUBLIC = 0;
    const GROUP_MEMBERSHIP_APPROVAL = 1;
    const GROUP_MEMBERSHIP_PASSCODE = 2;

    const COUNT_PETITION_PER_MONTH = 30;

    const GROUP_TRANSPARENCY_PUBLIC = 'public';
    const GROUP_TRANSPARENCY_PRIVATE = 'private';
    const GROUP_TRANSPARENCY_SECRET = 'secret';
    const GROUP_TRANSPARENCY_TOP_SECRET = 'top-secret';

    const PERMISSIONS_NAME = 'permissions_name';
    const PERMISSIONS_ADDRESS = 'permissions_address';
    const PERMISSIONS_CITY = 'permissions_city';
    const PERMISSIONS_STATE = 'permissions_state';
    const PERMISSIONS_COUNTRY = 'permissions_country';
    const PERMISSIONS_ZIP_CODE = 'permissions_zip_code';
    const PERMISSIONS_EMAIL = 'permissions_email';
    const PERMISSIONS_PHONE = 'permissions_phone';
    const PERMISSIONS_RESPONSES = 'permissions_responses';

    const CONVERSATION_VIEW_LIMIT = 10;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll", "api-groups", "api-search", "api-poll-public",
     *      "api-petitions-list", "api-petitions-info", "api-info", "api-invites-create", "api-invites"}
     * )
     */
    private $id;

    /**
     * @var int
     * 
     * @ORM\Column(name="group_type", type="smallint")
     * @Serializer\Expose()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll", "api-groups", "api-search", "api-poll-public",
     *      "api-petitions-list", "api-petitions-info", "api-info", "api-invites", "api-create-by-user"}
     * )
     * @Serializer\Until("1")
     */
    private $groupType = self::GROUP_TYPE_COMMON;

    /**
     * @var string
     *
     * @ORM\Column(name="manager_first_name", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-create-by-user", "api-group", "api-info"})
     */
    private $managerFirstName;

    /**
     * @var string
     *
     * @ORM\Column(name="manager_last_name", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-create-by-user", "api-group", "api-info"})
     */
    private $managerLastName;

    /**
     * @var string
     *
     * @ORM\Column(name="manager_email", type="string", length=255, nullable=true)
     * @Assert\Email(groups={"registration", "api-registration"})
     * @Assert\NotBlank(groups={"registration", "api-registration"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-create-by-user", "api-group", "api-info"})
     */
    private $managerEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="manager_phone", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-create-by-user", "api-group"})
     */
    private $managerPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="official_name", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"registration", "user-registration", "api-registration"})
     * @Serializer\Expose()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll","api-groups", "api-info", "api-search", "api-create-by-user",
     *      "api-petitions-list", "api-petitions-info", "api-invites", "api-poll-public", "api-group"}
     * )
     */
    private $officialName;

    /**
     * @var string
     *
     * @ORM\Column(name="official_description", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-create-by-user", "api-group"})
     */
    private $officialDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="acronym", type="string", length=4, nullable=true)
     * @Assert\Length(min = 2, max = 4, groups={"registration", "profile", "api-registration"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-groups", "api-poll-public", "api-create-by-user", "api-group"})
     * @Serializer\Accessor(getter="getAcronym")
     */
    private $acronym;

    /**
     * @var string
     *
     * @ORM\Column(name="official_type", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"user-registration"})
     * @Assert\Choice(callback="getOfficialTypes", groups={"user-registration"}, strict=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-create-by-user", "api-group"})
     */
    private $officialType;

    /**
     * @var string
     *
     * @ORM\Column(name="official_address", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-create-by-user", "api-group"})
     */
    private $officialAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="official_city", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-create-by-user", "api-group"})
     */
    private $officialCity;

    /**
     * @var string
     *
     * @ORM\Column(name="official_state", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info", "api-create-by-user", "api-group"})
     */
    private $officialState;

    /**
     * Group members
     * 
     * @ORM\OneToMany(targetEntity="UserGroup", mappedBy="group", fetch="EXTRA_LAZY")
     */
    private $users;
    
    /**
     * Group managers (that are group members too)
     *
     * @ORM\OneToMany(targetEntity="UserGroupManager", mappedBy="group", cascade={"persist"})
     */
    private $managers;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="invites")
     */
    private $invites;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-full-info"})
     */
    private $owner;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     * @Serializer\Since("2")
     * @Serializer\Groups({"api-groups"})
     */
    private $createdAt;

    /**
     * @Assert\Regex(
     *      pattern="/^\d+$/",
     *      message="The value cannot contain a non-numerical symbols"
     * )
     * @ORM\Column(name="questions_limit", type="integer", nullable=true)
     *
     * @var int
     */
    private $questionLimit;

    /**
     * @Assert\Type(type="integer", groups={"micropetition-config"})
     * @Assert\Range(
     *     min = 1,
     *     max = 50,
     *     groups={"micropetition-config"}
     * )
     * @ORM\Column(name="petition_percent", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"micropetition-config"})
     */
    private $petitionPercent = PetitionManager::PERCENT_IN_GROUP;

    /**
     * @Assert\Type(type="integer", groups={"micropetition-config"})
     * @Assert\Range(
     *     min = 1,
     *     max = 30,
     *     groups={"micropetition-config"}
     * )
     * @ORM\Column(name="petition_duration", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"micropetition-config"})
     */
    private $petitionDuration = PetitionManager::EXPIRE_INTERVAL;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups", "api-info", "api-invites", "membership-control"})
     * @Serializer\Accessor(getter="getMembershipControlTitle")
     * @Serializer\Type("string")
     * @ORM\Column(
     *      name="membership_control",
     *      type="smallint",
     *      nullable=false,
     *      options={"default" = 0}
     * )
     * @Assert\NotBlank(groups={"membership-control"})
     * @Assert\Choice(callback="getMembershipControlTypes", groups={"membership-control"}, strict=true)
     */
    private $membershipControl = self::GROUP_MEMBERSHIP_PUBLIC;

    /**
     * @ORM\Column(name="membership_passcode", type="string", nullable=true)
     * @Assert\NotBlank(groups={"membership-control-passcode"})
     */
    private $membershipPasscode;

    /**
     * @ORM\ManyToOne(targetEntity="State", inversedBy="localGroups")
     * @ORM\JoinColumn(name="local_state", referencedColumnName="code", onDelete="SET NULL")
     */
    private $localState;

    /**
     * @ORM\ManyToOne(targetEntity="District")
     * @ORM\JoinColumn(name="local_district", referencedColumnName="id", onDelete="SET NULL")
     */
    private $localDistrict;

    /**
     * @ORM\OneToMany(targetEntity="Representative", mappedBy="localGroup", cascade={"persist"})
     */
    private $localRepresentatives;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Civix\CoreBundle\Entity\Group\GroupField",
     *      mappedBy="group",
     *      cascade={"persist"},
     *      orphanRemoval=true
     * )
     * @Assert\Count(
     *      max = "5",
     *      maxMessage = "You can add up to 5 fields",
     *      groups = {"fields"}
     * )
     */
    private $fields;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Civix\CoreBundle\Entity\GroupSection",
     *     mappedBy="group",
     *     cascade={"persist"},
     *     fetch="EXTRA_LAZY",
     *     orphanRemoval=true
     * )
     */
    private $groupSections;

    /**
     * @ORM\Column(name="fill_fields_required", type="boolean", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups", "api-info", "api-invites"})
     */
    private $fillFieldsRequired = false;

    /**
     * @Assert\Type(type="integer", groups={"micropetition-config"})
     * @Assert\Range(
     *     min = 1,
     *     groups={"micropetition-config"}
     * )
     * @ORM\Column(
     *      name="petition_per_month",
     *      type="integer",
     *      nullable=false,
     *      options={"default" = 30}
     * )
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups", "micropetition-config"})
     * @Serializer\Accessor(getter="getPetitionPerMonth")
     *
     * @var int
     */
    private $petitionPerMonth = self::COUNT_PETITION_PER_MONTH;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"permission-settings"})
     * @Serializer\Accessor(getter="serializeRequiredPermissions")
     * @ORM\Column(name="required_permissions", type="array", nullable=true)
     */
    private $requiredPermissions = [
        self::PERMISSIONS_NAME,
        self::PERMISSIONS_COUNTRY,
        self::PERMISSIONS_RESPONSES,
    ];

    /**
     * @var DateTime
     * @Serializer\Expose()
     * @Serializer\Groups({"permission-settings"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s'>")
     * @ORM\Column(name="permission_changed_at", type="datetime", nullable=true)
     */
    private $permissionsChangedAt;

    /**
     * @var Group
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll"})
     * @Serializer\SerializedName("group")
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="children")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $parent;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Group", mappedBy="parent")
     */
    private $children;

    /**
     * @var string
     *
     * @ORM\Column(name="location_name", type="string", nullable=true)
     */
    private $locationName;

    /**
     * @var string
     *
     * @ORM\Column(name="transparency", type="string", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-full-info", "api-info"})
     * @Assert\NotBlank(groups={"Default", "user-registration"})
     * @Assert\Choice(callback="getTransparencyStates", groups={"Default", "user-registration"}, strict=true)
     */
    private $transparency = self::GROUP_TRANSPARENCY_PUBLIC;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", unique=true)
     */
    private $slug;

    /**
     * @var AdvancedAttributes
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Group\AdvancedAttributes", inversedBy="group")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    private $advancedAttributes;

    /**
     * @var Link[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\Group\Link", mappedBy="group", fetch="EXTRA_LAZY", cascade={"persist"}, orphanRemoval=true)
     */
    private $links;

    /**
     * @var File
     *
     * @ORM\Embedded(class="Civix\CoreBundle\Entity\File", columnPrefix="")
     */
    protected $banner;

    /**
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Civix\CoreBundle\Entity\Group\Tag", cascade={"persist"})
     */
    private $tags;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="conversation_view_limit", options={"default" = 10})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-full-info", "api-info", "group-list"})
     * @Assert\NotBlank(groups={"Default", "user-registration"})
     * @Assert\GreaterThanOrEqual(value="1", groups={"Default", "user-registration"})
     */
    private $conversationViewLimit = self::CONVERSATION_VIEW_LIMIT;

    /**
     * @return array
     */
    public static function getOfficialTypes(): array
    {
        return [
            'Educational' => 'Educational',
            'Non-Profit (Not Campaign)' => 'Non-Profit (Not Campaign)',
            'Non-Profit (Campaign)' => 'Non-Profit (Campaign)',
            'Business' => 'Business',
            'Cooperative/Union' => 'Cooperative/Union',
            'Other' => 'Other',
        ];
    }

    public static function getGroupTypes(): array
    {
        return [
            self::GROUP_TYPE_COMMON => 'common',
            self::GROUP_TYPE_COUNTRY => 'country',
            self::GROUP_TYPE_STATE => 'state',
            self::GROUP_TYPE_LOCAL => 'local',
            self::GROUP_TYPE_SPECIAL => 'special',
        ];
    }

    public static function getLocalTypes(): array
    {
        return [
            self::GROUP_TYPE_COUNTRY,
            self::GROUP_TYPE_STATE,
            self::GROUP_TYPE_LOCAL,
        ];
    }

    /**
     * @return array
     */
    public static function getMembershipControlTypes(): array
    {
        return [
            self::GROUP_MEMBERSHIP_PUBLIC,
            self::GROUP_MEMBERSHIP_APPROVAL,
            self::GROUP_MEMBERSHIP_PASSCODE,
        ];
    }

    /**
     * @return array
     */
    public static function getMembershipControlChoices(): array
    {
        return [
            self::GROUP_MEMBERSHIP_PUBLIC => 'public',
            self::GROUP_MEMBERSHIP_APPROVAL => 'approval',
            self::GROUP_MEMBERSHIP_PASSCODE => 'passcode',
        ];
    }

    public static function getTransparencyStates(): array
    {
        return [
            self::GROUP_TRANSPARENCY_PUBLIC,
            self::GROUP_TRANSPARENCY_PRIVATE,
            self::GROUP_TRANSPARENCY_SECRET,
            self::GROUP_TRANSPARENCY_TOP_SECRET,
        ];
    }

    public static function getPermissions(): array
    {
        return [
            self::PERMISSIONS_NAME => 'Name',
            self::PERMISSIONS_ADDRESS => 'Street Address',
            self::PERMISSIONS_CITY => 'City',
            self::PERMISSIONS_STATE => 'State',
            self::PERMISSIONS_COUNTRY => 'Country',
            self::PERMISSIONS_ZIP_CODE => 'Zip Code',
            self::PERMISSIONS_EMAIL => 'Email',
            self::PERMISSIONS_PHONE => 'Phone Number',
            self::PERMISSIONS_RESPONSES => 'Responses',
        ];
    }

    public static function getGeoTypes(): array
    {
        return [
            self::GROUP_TYPE_COUNTRY,
            self::GROUP_TYPE_STATE,
            self::GROUP_TYPE_LOCAL,
        ];
    }

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->managers = new ArrayCollection();
        $this->invites = new ArrayCollection();
        $this->localRepresentatives = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->groupSections = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->banner = new File();
    }

    /**
     * Add invite.
     *
     * @param User $user
     *
     * @return Group
     */
    public function addInvite(User $user): Group
    {
        $this->invites[] = $user;

        return $this;
    }

    /**
     * Remove invite.
     *
     * @param User $user
     */
    public function removeInvite(User $user): void
    {
        $this->invites->removeElement($user);
    }

    /**
     * Get invites.
     *
     * @return Collection
     */
    public function getInvites(): Collection
    {
        return $this->invites;
    }

    /**
     * Get type.
     *
     * @return string
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-activities", "api-poll", "api-search", "api-invites", "api-poll-public"})
     */
    public function getType(): string
    {
        return 'group';
    }

    /**
     * Get avatarSrc.
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll","api-groups", "api-info", "api-search",
     *      "api-petitions-list", "api-petitions-info", "api-invites", "api-poll-public"}
     * )
     * @Serializer\Type("Avatar")
     * @Serializer\SerializedName("avatar_file_path")
     * @return Avatar
     */
    public function getAvatarFilePath(): Avatar
    {
        return new Avatar($this);
    }

    /**
     * Get banner image
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll","api-groups", "api-info", "api-search",
     *      "api-petitions-list", "api-petitions-info", "api-invites", "api-poll-public"}
     * )
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("banner")
     * @return Image
     */
    public function getBannerImage(): Image
    {
        return new Image($this, 'banner.file', null, false);
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
     * @Serializer\VirtualProperty()
     * @Serializer\Type("TotalMembers")
     * @Serializer\Groups({"api-full-info"})
     */
    public function getTotalMembers(): TotalMembers
    {
        return new TotalMembers($this);
    }

    /**
     * @param Group $group
     *
     * @return bool
     */
    public function isEqualTo(Group $group): bool
    {
        return $this->getId() === $group->getId();
    }

    /**
     * Serializes the group.
     *
     * @return string
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
        ]);
    }

    /**
     * Unserializes the group.
     *
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        [$this->id] = unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getOfficialName();
    }

    /**
     * Set managerFirstName.
     *
     * @param string $managerFirstName
     *
     * @return Group
     */
    public function setManagerFirstName($managerFirstName): Group
    {
        $this->managerFirstName = $managerFirstName;

        return $this;
    }

    /**
     * Get managerFirstName.
     *
     * @return string
     */
    public function getManagerFirstName(): ?string
    {
        return $this->managerFirstName;
    }

    /**
     * Set managerLastName.
     *
     * @param string $managerLastName
     *
     * @return Group
     */
    public function setManagerLastName(?string $managerLastName): Group
    {
        $this->managerLastName = $managerLastName;

        return $this;
    }

    /**
     * Get managerLastName.
     *
     * @return string
     */
    public function getManagerLastName(): ?string
    {
        return $this->managerLastName;
    }

    /**
     * Set managerEmail.
     *
     * @param string $managerEmail
     *
     * @return Group
     */
    public function setManagerEmail(?string $managerEmail): Group
    {
        $this->managerEmail = $managerEmail;

        return $this;
    }

    /**
     * Get manager full name.
     * 
     * @return string
     */
    public function getManagerFullName(): string
    {
        return $this->getManagerFirstName().' '.$this->getManagerLastName();
    }

    /**
     * Get managerEmail.
     *
     * @return string
     */
    public function getManagerEmail(): ?string
    {
        return $this->managerEmail;
    }

    /**
     * Set managerPhone.
     *
     * @param string $managerPhone
     *
     * @return Group
     */
    public function setManagerPhone(?string $managerPhone): Group
    {
        $this->managerPhone = $managerPhone;

        return $this;
    }

    /**
     * Get managerPhone.
     *
     * @return string
     */
    public function getManagerPhone(): ?string
    {
        return $this->managerPhone;
    }

    /**
     * Set officialName.
     *
     * @param string $officialName
     *
     * @return Group
     */
    public function setOfficialName(?string $officialName): Group
    {
        $this->officialName = $officialName;

        return $this;
    }

    /**
     * Get officialName.
     *
     * @return string
     */
    public function getOfficialName(): ?string
    {
        return $this->officialName;
    }

    /**
     * Set officialDescription.
     *
     * @param string $officialDescription
     *
     * @return Group
     */
    public function setOfficialDescription(?string $officialDescription): Group
    {
        $this->officialDescription = $officialDescription;

        return $this;
    }

    /**
     * Get officialDescription.
     *
     * @return string
     */
    public function getOfficialDescription(): ?string
    {
        return $this->officialDescription;
    }

    /**
     * Set officialType.
     *
     * @param string $officialType
     *
     * @return Group
     */
    public function setOfficialType(?string $officialType): Group
    {
        $this->officialType = $officialType;

        return $this;
    }

    /**
     * Get officialType.
     *
     * @return string
     */
    public function getOfficialType(): ?string
    {
        return $this->officialType;
    }

    /**
     * Set officialAddress.
     *
     * @param string $officialAddress
     *
     * @return Group
     */
    public function setOfficialAddress(?string $officialAddress): Group
    {
        $this->officialAddress = $officialAddress;

        return $this;
    }

    /**
     * Get officialAddress.
     *
     * @return string
     */
    public function getOfficialAddress(): ?string
    {
        return $this->officialAddress;
    }

    /**
     * Set officialCity.
     *
     * @param string $officialCity
     *
     * @return Group
     */
    public function setOfficialCity(?string $officialCity): Group
    {
        $this->officialCity = $officialCity;

        return $this;
    }

    /**
     * Get officialCity.
     *
     * @return string
     */
    public function getOfficialCity(): ?string
    {
        return $this->officialCity;
    }

    /**
     * Set officialState.
     *
     * @param string $officialState
     *
     * @return Group
     */
    public function setOfficialState(?string $officialState): Group
    {
        $this->officialState = $officialState;

        return $this;
    }

    /**
     * Get officialState.
     *
     * @return string
     */
    public function getOfficialState(): ?string
    {
        return $this->officialState;
    }

    /**
     * Get default avatar.
     *
     * @return DefaultAvatarInterface
     */
    public function getDefaultAvatar(): DefaultAvatarInterface
    {
        if (in_array($this->groupType, self::getGeoTypes(), true)) {
            return new DefaultAvatar(self::DEFAULT_MAP_AVATAR);
        }

        return new FirstLetterDefaultAvatar($this->officialName);
    }

    /**
     * @return JoinStatus
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-groups", "api-info"})
     * @Serializer\Type("JoinStatus")
     * @Serializer\SerializedName("joined")
     */
    public function getJoinStatus(): JoinStatus
    {
        return new JoinStatus($this);
    }

    /**
     * Get Join status.
     *
     * @param User $user
     * @return int
     */
    public function getJoined(User $user): int
    {
        return $user->getGroups()->contains($this) ? 1 : 0;
    }

    /**
     * Checks if a user belongs as group member to the current group
     *
     * @param UserInterface $user
     * @return bool
     */
    public function isMember(UserInterface $user): bool
    {
        return $this->users->filter(function (UserGroup $userGroup) use ($user) {
            return $userGroup->isActive() && $userGroup->getUser()->isEqualTo($user);
        })->count() > 0;
    }

    /**
     * Checks if a user belongs as group manager to the current group
     *
     * @param UserInterface $user
     * @return bool
     */
    public function isManager(UserInterface $user): bool
    {
    	return $this->getManagers()->contains($user);
    }

    /**
     * Add users.
     *
     * @param UserGroup $user
     *
     * @return Group
     */
    public function addUser(UserGroup $user): Group
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove users.
     *
     * @param UserGroup $user
     */
    public function removeUser(UserGroup $user): void
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return new ArrayCollection(array_map(
            function (UserGroup $userGroup) {
                return $userGroup->getUser();
            },
            $this->users->toArray()
        ));
    }
    
    /**
     * Add group manager users.
     *
     * @param \Civix\CoreBundle\Entity\UserGroupManager $manager
     *
     * @return Group
     */
    public function addManager(UserGroupManager $manager): Group
    {
    	$this->managers[] = $manager;
    
    	return $this;
    }
    
    /**
     * Remove group manager users.
     *
     * @param \Civix\CoreBundle\Entity\UserGroupManager $manager
     */
    public function removeManager(UserGroupManager $manager): void
    {
    	$this->managers->removeElement($manager);
    }
    
    /**
     * Get users.
     *
     * @return Collection
     */
    public function getManagers(): Collection
    {
    	return new ArrayCollection(array_map(
    			function (UserGroupManager $userGroupManager) {
    				return $userGroupManager->getUser();
    			},
    			$this->managers->toArray()
    			));
    }

    /**
     * Check if a User give is really the group owner for a 
     * group
     * 
     * @param User $user
     * 
     * @return boolean
     */
    public function isOwner(User $user): bool
    {
    	return $this->getOwner() ? $user->isEqualTo($this->getOwner()) : false;
    }
    
    /**
     * @return User
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     *
     * @return $this
     */
    public function setOwner(?User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->owner;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(?DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * Get limit of question.
     *
     * @return int
     */
    public function getQuestionLimit(): ?int
    {
        return $this->questionLimit;
    }

    /**
     * Set limit of question.
     *
     * @param int $limit
     * @return Group
     */
    public function setQuestionLimit(?int $limit): Group
    {
        $this->questionLimit = $limit;

        return $this;
    }

    /**
     * Get petition's percent.
     *
     * @return int
     */
    public function getPetitionPercent(): ?int
    {
        return empty($this->petitionPercent) ?
            PetitionManager::PERCENT_IN_GROUP : $this->petitionPercent;
    }

    /**
     * Set petition's percent.
     *
     * @param $percent
     * @return Group
     */
    public function setPetitionPercent(?int $percent): Group
    {
        $this->petitionPercent = $percent;

        return $this;
    }

    /**
     * Get petition's duration.
     *
     * @return int
     */
    public function getPetitionDuration(): ?int
    {
        return empty($this->petitionDuration) ?
            PetitionManager::EXPIRE_INTERVAL : $this->petitionDuration;
    }

    /**
     * Set petition's duration.
     *
     * @param $duration
     * @return Group
     */
    public function setPetitionDuration(?int $duration): Group
    {
        $this->petitionDuration = $duration;

        return $this;
    }

    public function getGroupType(): ?int
    {
        return $this->groupType;
    }

    /**
     * @return mixed|null
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll", "api-groups", "api-search", "api-poll-public",
     *      "api-petitions-list", "api-petitions-info", "api-info", "api-invites", "api-create-by-user"}
     * )
     * @Serializer\Since("2")
     */
    public function getGroupTypeLabel(): ?string
    {
        $groupTypes = self::getGroupTypes();
        if (isset($groupTypes[$this->groupType])) {
            return $groupTypes[$this->groupType];
        }

        return null;
    }

    public function setGroupType(?int $type): Group
    {
        $this->groupType = $type;

        return $this;
    }

    /**
     * Set localState.
     *
     * @param State $localState
     *
     * @return Group
     */
    public function setLocalState(?State $localState): Group
    {
        $this->localState = $localState;

        return $this;
    }

    /**
     * Get localState.
     *
     * @return State
     */
    public function getLocalState(): ?State
    {
        return $this->localState;
    }

    /**
     * Get localDistrictId.
     *
     * @return int
     */
    public function getLocalDistrictId(): ?int
    {
        $district = $this->getLocalDistrict();

        return $district ? $district->getId() : null;
    }

    /**
     * Add localRepresentatives.
     *
     * @param Representative $localRepresentative
     *
     * @return Group
     */
    public function addLocalRepresentative(Representative $localRepresentative): Group
    {
        $localRepresentative->setLocalGroup($this);
        $this->localRepresentatives[] = $localRepresentative;

        return $this;
    }

    /**
     * Remove localRepresentatives.
     *
     * @param Representative $localRepresentative
     */
    public function removeLocalRepresentative(Representative $localRepresentative): void
    {
        $localRepresentative->setLocalGroup();
        $this->localRepresentatives->removeElement($localRepresentative);
    }

    /**
     * Get localRepresentatives.
     *
     * @return Collection
     */
    public function getLocalRepresentatives(): Collection
    {
        return $this->localRepresentatives;
    }

    /**
     * Set localDistrict.
     *
     * @param District $localDistrict
     *
     * @return Group
     */
    public function setLocalDistrict(?District $localDistrict): Group
    {
        $this->localDistrict = $localDistrict;

        return $this;
    }

    /**
     * Get localDistrict.
     *
     * @return District
     */
    public function getLocalDistrict(): ?District
    {
        return $this->localDistrict;
    }

    /**
     * Set membershipControl.
     *
     * @param int $membershipControl
     *
     * @return Group
     */
    public function setMembershipControl(?int $membershipControl): Group
    {
        $this->membershipControl = $membershipControl;

        return $this;
    }

    /**
     * Get membershipControl.
     *
     * @return int
     */
    public function getMembershipControl(): ?int
    {
        return $this->membershipControl;
    }

    /**
     * @return string|null
     */
    public function getMembershipControlTitle(): ?string
    {
        $choices = self::getMembershipControlChoices();
        $membershipControl = $this->getMembershipControl();
        if (isset($choices[$membershipControl])) {
            return $choices[$membershipControl];
        }
        
        return null;
    }

    /**
     * Set membershipPasscode.
     *
     * @param string $membershipPasscode
     *
     * @return Group
     */
    public function setMembershipPasscode(?string $membershipPasscode): Group
    {
        $this->membershipPasscode = $membershipPasscode;

        return $this;
    }

    /**
     * Get membershipPasscode.
     *
     * @return string
     */
    public function getMembershipPasscode(): ?string
    {
        return $this->membershipPasscode;
    }

    /**
     * Add field.
     *
     * @param Group\GroupField $field
     *
     * @return Group
     */
    public function addField(Group\GroupField $field): Group
    {
        $this->fields[] = $field;
        $field->setGroup($this);

        return $this;
    }

    /**
     * Remove fields.
     *
     * @param Group\GroupField $fields
     */
    public function removeField(Group\GroupField $fields): void
    {
        $this->fields->removeElement($fields);
    }

    /**
     * Get fields.
     *
     * @return Collection|GroupField[]
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * Set fillFieldsRequired.
     *
     * @param bool $fillFieldsRequired
     *
     * @return Group
     */
    public function setFillFieldsRequired(?bool $fillFieldsRequired): Group
    {
        $this->fillFieldsRequired = $fillFieldsRequired;

        return $this;
    }

    /**
     * Get fillFieldsRequired.
     *
     * @return bool
     */
    public function getFillFieldsRequired(): ?bool
    {
        return $this->fillFieldsRequired;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateFillFieldsRequired(): void
    {
        $this->fillFieldsRequired = (boolean) $this->fields->count();
    }

    public function getFieldsIds(): array
    {
        return $this->fields->count() > 0 ? $this->fields->map(function (GroupField $groupField) {
                return $groupField->getId();
        })->toArray() : [];
    }

    /**
     * Set petitionPerMonth.
     *
     * @param int $petitionPerMonth
     *
     * @return Group
     */
    public function setPetitionPerMonth(?int $petitionPerMonth): Group
    {
        $this->petitionPerMonth = $petitionPerMonth;

        return $this;
    }

    /**
     * Get petitionPerMonth.
     *
     * @return int
     */
    public function getPetitionPerMonth(): int
    {
        return $this->petitionPerMonth ?? self::COUNT_PETITION_PER_MONTH;
    }

    /**
     * Set acronym.
     *
     * @param string $acronym
     *
     * @return Group
     */
    public function setAcronym(?string $acronym): Group
    {
        $this->acronym = $acronym;

        return $this;
    }

    /**
     * Get acronym.
     *
     * @return string
     */
    public function getAcronym(): ?string
    {
        return $this->acronym ?: $this->getDefaultAcronym();
    }

    public function getDefaultAcronym(): ?string
    {
        $type = $this->getGroupType();
        if (in_array($type, [self::GROUP_TYPE_COUNTRY, self::GROUP_TYPE_STATE], true)) {
            return $this->getLocationName();
        }
        
        return null;
    }

    public function getAddressArray(): array
    {
        return [
            'city' => $this->getOfficialCity(),
            'line1' => $this->getOfficialAddress(),
            'line2' => '',
            'state' => $this->getOfficialState(),
            'postal_code' => '',
            'country_code' => 'US',
        ];
    }

    public function isCommercial(): bool
    {
        return $this->getOfficialType() === 'Business';
    }

    public function getEmail(): ?string
    {
        return $this->getManagerEmail();
    }

    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    public function serializeRequiredPermissions(): array
    {
        if (is_array($this->requiredPermissions)) {
            return array_values($this->requiredPermissions);
        }

        return [];
    }

    public function setRequiredPermissions(array $permissions): Group
    {
        $this->requiredPermissions = $permissions;

        return $this;
    }

    public function hasRequiredPermissions($key): bool
    {
        return in_array($key, $this->requiredPermissions, true);
    }

    public function setPermissionsChangedAt(?DateTime $date): Group
    {
        $this->permissionsChangedAt = $date;

        return $this;
    }

    public function getPermissionsChangedAt(): ?DateTime
    {
        return $this->permissionsChangedAt;
    }

    public function getGroupSections(): Collection
    {
        return $this->groupSections;
    }

    public function addGroupSection(GroupSection $groupSection): Group
    {
        $this->groupSections[] = $groupSection;
        $groupSection->setGroup($this);

        return $this;
    }

    public function removeGroupSection(GroupSection $groupSection): Group
    {
        $this->groupSections->removeElement($groupSection);

        return $this;
    }

    public function setGroupSections(Collection $groupSection)
    {
        $this->groupSections = $groupSection;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * @param string $locationName
     *
     * @return $this
     */
    public function setLocationName(?string $locationName): Group
    {
        $this->locationName = $locationName;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return Collection
     */
    public function getLocalChildren(): Collection
    {
        return $this->children->filter(function(Group $group) {
            return $group->isLocalGroup();
        });
    }

    /**
     * @param mixed $children
     *
     * @return $this
     */
    public function setChildren(Group $children): Group
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return Group
     */
    public function getParent(): ?Group
    {
        return $this->parent;
    }

    /**
     * @param Group $parent
     *
     * @return $this
     */
    public function setParent(?Group $parent): Group
    {
        $this->parent = $parent;

        return $this;
    }

    public function isLocalGroup(): bool
    {
        return $this->groupType === self::GROUP_TYPE_LOCAL;
    }

    public function isCountryGroup(): bool
    {
        return $this->groupType === self::GROUP_TYPE_COUNTRY;
    }

    public function isStateGroup(): bool
    {
        return $this->groupType === self::GROUP_TYPE_STATE;
    }

    /**
     * Set transparency
     *
     * @param string $transparency
     * @return Group
     */
    public function setTransparency(?string $transparency): Group
    {
        $this->transparency = $transparency;
    
        return $this;
    }

    /**
     * Get transparency
     *
     * @return string 
     */
    public function getTransparency(): ?string
    {
        return $this->transparency;
    }

    /**
     * @return string
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return Group
     */
    public function setSlug(?string $slug): Group
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return UserRole
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"user-role"})
     * @Serializer\SerializedName("user_role")
     * @Serializer\Type("UserRole")
     */
    public function getUserRole(): ?UserRole
    {
        if ($this->users->count()) {
            return new UserRole($this->users->first());
        }

        return null;
    }

    public function getOfficialTitle(): ?string
    {
        return $this->getOfficialName();
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"micropetition-config"})
     * @Serializer\Type("ContentRemaining")
     * @return ContentRemaining
     */
    public function getPostsRemaining(): ContentRemaining
    {
        return new ContentRemaining('post', $this);
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"micropetition-config"})
     * @Serializer\Type("ContentRemaining")
     * @return ContentRemaining
     */
    public function getPetitionsRemaining(): ContentRemaining
    {
        return new ContentRemaining('petition', $this);
    }

    public function getUserGroups(): Collection
    {
        return $this->users;
    }

    /**
     * @return AdvancedAttributes
     */
    public function getAdvancedAttributes(): AdvancedAttributes
    {
        return $this->advancedAttributes;
    }

    public function getLinks(): Collection
    {
        return $this->links;
    }

    public function addLink(Link $link): Group
    {
        $this->links[] = $link;
        $link->setGroup($this);

        return $this;
    }

    public function removeLink(Link $link): Group
    {
        $this->links->removeElement($link);

        return $this;
    }

    /**
     * @return File
     */
    public function getBanner(): File
    {
        return $this->banner;
    }

    /**
     * @param File $banner
     * @return Group
     */
    public function setBanner(File $banner): Group
    {
        $this->banner = $banner;

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): Group
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function removeTag(Tag $tag): Group
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * @return int
     */
    public function getConversationViewLimit(): int
    {
        return $this->conversationViewLimit;
    }

    /**
     * @param int $conversationViewLimit
     * @return Group
     */
    public function setConversationViewLimit(int $conversationViewLimit): Group
    {
        $this->conversationViewLimit = $conversationViewLimit;

        return $this;
    }
}

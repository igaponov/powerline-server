<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Group\GroupField;
use Civix\CoreBundle\Serializer\Type\TotalMembers;
use Civix\CoreBundle\Serializer\Type\UserRole;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints as RecaptchaAssert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Serializer\Type\Avatar;
use Civix\CoreBundle\Model\CropAvatarInterface;
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
 * @Vich\Uploadable
 * @Serializer\ExclusionPolicy("all")
 */
class Group implements \Serializable, CheckingLimits, CropAvatarInterface, LeaderContentRootInterface, OfficialInterface
{
    use HasStripeAccountTrait, HasStripeCustomerTrait;

    const DEFAULT_AVATAR = '/bundles/civixfront/img/default_group.png';

    const GROUP_TYPE_COMMON = 0;
    const GROUP_TYPE_COUNTRY = 1;
    const GROUP_TYPE_STATE = 2;
    const GROUP_TYPE_LOCAL = 3;
    const GROUP_TYPE_SPECIAL = 4;

    const GROUP_LOCATION_NAME_EROPEAN_UNION = "EU";
    const GROUP_LOCATION_NAME_AFRICAN_UNION = "AFU";

    const GROUP_MEMBERSHIP_PUBLIC = 0;
    const GROUP_MEMBERSHIP_APPROVAL = 1;
    const GROUP_MEMBERSHIP_PASSCODE = 2;

    const COUNT_PETITION_PER_MONTH = 30;

    const GROUP_TRANSPARENCY_PUBLIC = "public";
    const GROUP_TRANSPARENCY_PRIVATE = "private";
    const GROUP_TRANSPARENCY_SECRET = "secret";
    const GROUP_TRANSPARENCY_TOP_SECRET = "top-secret";

    const PERMISSIONS_NAME = 'permissions_name';
    const PERMISSIONS_ADDRESS = 'permissions_address';
    const PERMISSIONS_CITY = 'permissions_city';
    const PERMISSIONS_STATE = 'permissions_state';
    const PERMISSIONS_COUNTRY = 'permissions_country';
    const PERMISSIONS_ZIP_CODE = 'permissions_zip_code';
    const PERMISSIONS_EMAIL = 'permissions_email';
    const PERMISSIONS_PHONE = 'permissions_phone';
    const PERMISSIONS_RESPONSES = 'permissions_responses';

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
     * @Serializer\Expose()
     * @Serializer\ReadOnly()
     * @Serializer\Groups({"api-activities", "api-poll", "api-search", "api-invites", "api-poll-public"})
     */
    private $type = 'group';

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
    private $groupType;

    /**
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes={"image/png", "image/jpeg", "image/pjpeg"},
     *     groups={"profile"}
     * )
     * @Vich\UploadableField(mapping="avatar_image", fileNameProperty="avatarFileName")
     *
     * @var File
     */
    private $avatar;

    /**
     * @ORM\Column(name="avatar_file_name", type="string", nullable=true)
     *
     * @var string
     */
    private $avatarFileName;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups(
     *      {"api-activities", "api-poll","api-groups", "api-info", "api-search",
     *      "api-petitions-list", "api-petitions-info", "api-invites", "api-poll-public"}
     * )
     * @Serializer\Type("Avatar")
     * @Serializer\Accessor(getter="getAvatarSrc")
     *
     * @var string
     */
    private $avatarFilePath;

    /**
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes={"image/png", "image/jpeg", "image/pjpeg"},
     *     groups={"profile"}
     * )
     * @Vich\UploadableField(mapping="avatar_source_image", fileNameProperty="avatarSourceFileName")
     *
     * @var File
     */
    private $avatarSource;

    /**
     * @ORM\Column(name="avatar_source_file_name", type="string", nullable=true)
     *
     * @var string
     */
    private $avatarSourceFileName;

    /**
     * @var string
     *
     * @ORM\Column(name="manager_first_name", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-create-by-user", "api-group"})
     */
    private $managerFirstName;

    /**
     * @var string
     *
     * @ORM\Column(name="manager_last_name", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-create-by-user", "api-group"})
     */
    private $managerLastName;

    /**
     * @var string
     *
     * @ORM\Column(name="manager_email", type="string", length=255, nullable=true)
     * @Assert\Email(groups={"registration", "api-registration"})
     * @Assert\NotBlank(groups={"registration", "api-registration"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-create-by-user", "api-group"})
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
     * @ORM\Column(name="official_name", type="string", length=255, nullable=true, unique=true)
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
     * @Assert\Choice(callback="getOfficialTypes", groups={"user-registration"})
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
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups"})
     * @Serializer\Accessor(getter="getPicture")
     */
    protected $picture;

    /**
     * @var \DateTime
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
     * @Assert\Choice(callback="getMembershipControlTypes", groups={"membership-control"})
     */
    private $membershipControl;

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
    private $requiredPermissions = [];

    /**
     * @var \DateTime
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
     * @Serializer\Groups({"api-full-info"})
     * @Assert\NotBlank(groups={"Default", "user-registration"})
     * @Assert\Choice(callback="getTransparencyStates", groups={"Default", "user-registration"})
     */
    private $transparency;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", unique=true)
     */
    private $slug;

    /**
     * @return array
     */
    public static function getOfficialTypes()
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

    public static function getGroupTypes()
    {
        return [
            self::GROUP_TYPE_COMMON => 'common',
            self::GROUP_TYPE_COUNTRY => 'country',
            self::GROUP_TYPE_STATE => 'state',
            self::GROUP_TYPE_LOCAL => 'local',
            self::GROUP_TYPE_SPECIAL => 'special',
        ];
    }

    /**
     * @return array
     */
    public static function getMembershipControlTypes()
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
    public static function getMembershipControlChoices()
    {
        return [
            self::GROUP_MEMBERSHIP_PUBLIC => 'public',
            self::GROUP_MEMBERSHIP_APPROVAL => 'approval',
            self::GROUP_MEMBERSHIP_PASSCODE => 'passcode',
        ];
    }

    public static function getTransparencyStates()
    {
        return [
            self::GROUP_TRANSPARENCY_PUBLIC,
            self::GROUP_TRANSPARENCY_PRIVATE,
            self::GROUP_TRANSPARENCY_SECRET,
            self::GROUP_TRANSPARENCY_TOP_SECRET,
        ];
    }

    public static function getPermissions()
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

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->managers = new ArrayCollection();
        $this->invites = new ArrayCollection();
        $this->localRepresentatives = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->groupSections = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->groupType = self::GROUP_TYPE_COMMON;
        $this->membershipControl = self::GROUP_MEMBERSHIP_PUBLIC;
        $this->petitionPerMonth = self::COUNT_PETITION_PER_MONTH;
        $this->transparency = self::GROUP_TRANSPARENCY_PUBLIC;
        $this->requiredPermissions = [
            self::PERMISSIONS_NAME,
            self::PERMISSIONS_COUNTRY,
            self::PERMISSIONS_RESPONSES,
        ];
    }

    /**
     * Add invite.
     *
     * @param User $user
     *
     * @return Group
     */
    public function addInvite(User $user)
    {
        $this->invites[] = $user;

        return $this;
    }

    /**
     * Remove invite.
     *
     * @param User $user
     */
    public function removeInvite(User $user)
    {
        $this->invites->removeElement($user);
    }

    /**
     * Get invites.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInvites()
    {
        return $this->invites;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get avatarSrc.
     *
     * @return Avatar
     */
    public function getAvatarSrc()
    {
        return new Avatar($this);
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
     * @Serializer\VirtualProperty()
     * @Serializer\Type("TotalMembers")
     * @Serializer\Groups({"api-full-info"})
     */
    public function getTotalMembers()
    {
        return new TotalMembers($this);
    }

    /**
     * @param Group $group
     *
     * @return bool
     */
    public function isEqualTo(Group $group)
    {
        return $this->getId() == $group->getId();
    }

    /**
     * Serializes the group.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
                $this->id,
            ));
    }

    /**
     * Unserializes the group.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->id) = unserialize($serialized);
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
    public function setManagerFirstName($managerFirstName)
    {
        $this->managerFirstName = $managerFirstName;

        return $this;
    }

    /**
     * Get managerFirstName.
     *
     * @return string
     */
    public function getManagerFirstName()
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
    public function setManagerLastName($managerLastName)
    {
        $this->managerLastName = $managerLastName;

        return $this;
    }

    /**
     * Get managerLastName.
     *
     * @return string
     */
    public function getManagerLastName()
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
    public function setManagerEmail($managerEmail)
    {
        $this->managerEmail = $managerEmail;

        return $this;
    }

    /**
     * Get manager full name.
     * 
     * @return string
     */
    public function getManagerFullName()
    {
        return $this->getManagerFirstName().' '.$this->getManagerLastName();
    }

    /**
     * Get managerEmail.
     *
     * @return string
     */
    public function getManagerEmail()
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
    public function setManagerPhone($managerPhone)
    {
        $this->managerPhone = $managerPhone;

        return $this;
    }

    /**
     * Get managerPhone.
     *
     * @return string
     */
    public function getManagerPhone()
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
    public function setOfficialName($officialName)
    {
        $this->officialName = $officialName;

        return $this;
    }

    /**
     * Get officialName.
     *
     * @return string
     */
    public function getOfficialName()
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
    public function setOfficialDescription($officialDescription)
    {
        $this->officialDescription = $officialDescription;

        return $this;
    }

    /**
     * Get officialDescription.
     *
     * @return string
     */
    public function getOfficialDescription()
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
    public function setOfficialType($officialType)
    {
        $this->officialType = $officialType;

        return $this;
    }

    /**
     * Get officialType.
     *
     * @return string
     */
    public function getOfficialType()
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
    public function setOfficialAddress($officialAddress)
    {
        $this->officialAddress = $officialAddress;

        return $this;
    }

    /**
     * Get officialAddress.
     *
     * @return string
     */
    public function getOfficialAddress()
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
    public function setOfficialCity($officialCity)
    {
        $this->officialCity = $officialCity;

        return $this;
    }

    /**
     * Get officialCity.
     *
     * @return string
     */
    public function getOfficialCity()
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
    public function setOfficialState($officialState)
    {
        $this->officialState = $officialState;

        return $this;
    }

    /**
     * Get officialState.
     *
     * @return string
     */
    public function getOfficialState()
    {
        return $this->officialState;
    }

    /**
     * Set avatar.
     *
     * @param UploadedFile $avatar
     *
     * @return Group
     */
    public function setAvatar(UploadedFile $avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar.
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Get default avatar.
     *
     * @return string
     */
    public function getDefaultAvatar()
    {
        return self::DEFAULT_AVATAR;
    }

    /**
     * Set avatarFileName.
     *
     * @param string $avatarFileName
     *
     * @return Group
     */
    public function setAvatarFileName($avatarFileName)
    {
        $this->avatarFileName = $avatarFileName;

        return $this;
    }

    /**
     * Get avatarFileName.
     *
     * @return string
     */
    public function getAvatarFileName()
    {
        return $this->avatarFileName;
    }

    /**
     * Set avatarSource.
     *
     * @param string $avatarSource
     *
     * @return Group
     */
    public function setAvatarSource($avatarSource)
    {
        $this->avatarSource = $avatarSource;

        return $this;
    }

    /**
     * Get avatarSource.
     *
     * @return string
     */
    public function getAvatarSource()
    {
        return $this->avatarSource;
    }

    /**
     * Set avatarSourceFileName.
     *
     * @param string $avatarSourceFileName
     *
     * @return Group
     */
    public function setAvatarSourceFileName($avatarSourceFileName)
    {
        $this->avatarSourceFileName = $avatarSourceFileName;

        return $this;
    }

    /**
     * Get avatarSourceFileName.
     *
     * @return string
     */
    public function getAvatarSourceFileName()
    {
        return $this->avatarSourceFileName;
    }

    /**
     * Set avatarFilePath.
     *
     * @param string $avatarFilePath
     *
     * @return Group
     */
    public function setAvatarFilePath($avatarFilePath)
    {
        $this->avatarFilePath = $avatarFilePath;

        return $this;
    }

    /**
     * Get avatarFilePath.
     *
     * @return string
     */
    public function getAvatarFilePath()
    {
        return $this->avatarFilePath;
    }

    /**
     * @return JoinStatus
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-groups", "api-info"})
     * @Serializer\Type("JoinStatus")
     * @Serializer\SerializedName("joined")
     */
    public function getJoinStatus()
    {
        return new JoinStatus($this);
    }

    /**
     * Get Join status.
     *
     * @param User $user
     * @return int
     */
    public function getJoined(User $user)
    {
        return $user->getGroups()->contains($this) ? 1 : 0;
    }

    /**
     * Checks if a user belongs as group member to the current group
     *
     * @param UserInterface $user
     * @return bool
     */
    public function isMember(UserInterface $user)
    {
        return $this->users->filter(function (UserGroup $usergroup) use ($user) {
            return $usergroup->isActive() && $usergroup->getUser()->isEqualTo($user);
        })->count() > 0;
    }

    /**
     * Checks if a user belongs as group manager to the current group
     *
     * @param UserInterface $user
     * @return bool
     */
    public function isManager(UserInterface $user)
    {
    	return $this->getManagers()->contains($user);
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
     * Add users.
     *
     * @param UserGroup $user
     *
     * @return Group
     */
    public function addUser(UserGroup $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove users.
     *
     * @param UserGroup $user
     */
    public function removeUser(UserGroup $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return new ArrayCollection(array_map(
            function (UserGroup $usergroup) {
                return $usergroup->getUser();
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
    public function addManager(UserGroupManager $manager)
    {
    	$this->managers[] = $manager;
    
    	return $this;
    }
    
    /**
     * Remove group manager users.
     *
     * @param \Civix\CoreBundle\Entity\UserGroupManager $manager
     */
    public function removeManager(UserGroupManager $manager)
    {
    	$this->managers->removeElement($manager);
    }
    
    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getManagers()
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
     * @param UserInterface $user
     * 
     * @return boolean
     */
    public function isOwner(UserInterface $user)
    {
    	return !empty($this->getOwner()) && $this->getOwner()->getId() === $user->getId();
    }
    
    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     *
     * @return $this
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->owner;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedDate()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return $this
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
     * Get limit of question.
     *
     * @return int
     */
    public function getQuestionLimit()
    {
        return $this->questionLimit;
    }

    /**
     * Set limit of question.
     *
     * @param int $limit
     * @return Group
     */
    public function setQuestionLimit($limit)
    {
        $this->questionLimit = $limit;

        return $this;
    }

    /**
     * Get petition's percent.
     *
     * @return int
     */
    public function getPetitionPercent()
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
    public function setPetitionPercent($percent)
    {
        $this->petitionPercent = $percent;

        return $this;
    }

    /**
     * Get petition's duration.
     *
     * @return int
     */
    public function getPetitionDuration()
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
    public function setPetitionDuration($duration)
    {
        $this->petitionDuration = $duration;

        return $this;
    }

    public function getGroupType()
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
    public function getGroupTypeLabel()
    {
        $groupTypes = self::getGroupTypes();
        if (isset($groupTypes[$this->groupType])) {
            return $groupTypes[$this->groupType];
        }

        return null;
    }

    public function setGroupType($type)
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
    public function setLocalState(State $localState = null)
    {
        $this->localState = $localState;

        return $this;
    }

    /**
     * Get localState.
     *
     * @return State
     */
    public function getLocalState()
    {
        return $this->localState;
    }

    /**
     * Get localDistrictId.
     *
     * @return int
     */
    public function getLocalDistrictId()
    {
        return $this->getLocalDistrict()->getId();
    }

    /**
     * Add localRepresentatives.
     *
     * @param Representative $localRepresentative
     *
     * @return Group
     */
    public function addLocalRepresentative(Representative $localRepresentative)
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
    public function removeLocalRepresentative(Representative $localRepresentative)
    {
        $localRepresentative->setLocalGroup(null);
        $this->localRepresentatives->removeElement($localRepresentative);
    }

    /**
     * Get localRepresentatives.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocalRepresentatives()
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
    public function setLocalDistrict(District $localDistrict = null)
    {
        $this->localDistrict = $localDistrict;

        return $this;
    }

    /**
     * Get localDistrict.
     *
     * @return District
     */
    public function getLocalDistrict()
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
    public function setMembershipControl($membershipControl)
    {
        $this->membershipControl = $membershipControl;

        return $this;
    }

    /**
     * Get membershipControl.
     *
     * @return int
     */
    public function getMembershipControl()
    {
        return $this->membershipControl;
    }

    /**
     * @return string|null
     */
    public function getMembershipControlTitle()
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
    public function setMembershipPasscode($membershipPasscode)
    {
        $this->membershipPasscode = $membershipPasscode;

        return $this;
    }

    /**
     * Get membershipPasscode.
     *
     * @return string
     */
    public function getMembershipPasscode()
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
    public function addField(Group\GroupField $field)
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
    public function removeField(Group\GroupField $fields)
    {
        $this->fields->removeElement($fields);
    }

    /**
     * Get fields.
     *
     * @return \Doctrine\Common\Collections\Collection|GroupField[]
     */
    public function getFields()
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
    public function setFillFieldsRequired($fillFieldsRequired)
    {
        $this->fillFieldsRequired = $fillFieldsRequired;

        return $this;
    }

    /**
     * Get fillFieldsRequired.
     *
     * @return bool
     */
    public function getFillFieldsRequired()
    {
        return $this->fillFieldsRequired;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateFillFieldsRequired()
    {
        $this->fillFieldsRequired = (boolean) $this->fields->count();
    }

    public function getFieldsIds()
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
    public function setPetitionPerMonth($petitionPerMonth)
    {
        $this->petitionPerMonth = $petitionPerMonth;

        return $this;
    }

    /**
     * Get petitionPerMonth.
     *
     * @return int
     */
    public function getPetitionPerMonth()
    {
        return $this->petitionPerMonth === null ? self::COUNT_PETITION_PER_MONTH : $this->petitionPerMonth;
    }

    /**
     * Set acronym.
     *
     * @param string $acronym
     *
     * @return Group
     */
    public function setAcronym($acronym)
    {
        $this->acronym = $acronym;

        return $this;
    }

    /**
     * Get acronym.
     *
     * @return string
     */
    public function getAcronym()
    {
        return $this->acronym ?: $this->getDefaultAcronym();
    }

    public function getDefaultAcronym()
    {
        if (self::GROUP_TYPE_COUNTRY === $this->getGroupType() || self::GROUP_TYPE_STATE === $this->getGroupType()) {
            return $this->getLocationName();
        }
        
        return null;
    }

    public function getAddressArray()
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

    public function isCommercial()
    {
        return $this->getOfficialType() === 'Business';
    }

    public function getEmail()
    {
        return $this->getManagerEmail();
    }

    public function getRequiredPermissions()
    {
        return $this->requiredPermissions;
    }

    public function serializeRequiredPermissions()
    {
        if (is_array($this->requiredPermissions)) {
            return array_values($this->requiredPermissions);
        }

        return [];
    }

    public function setRequiredPermissions($permissions)
    {
        $this->requiredPermissions = $permissions;

        return $this;
    }

    public function hasRequiredPermissions($key)
    {
        return in_array($key, $this->requiredPermissions);
    }

    public function setPermissionsChangedAt($date)
    {
        $this->permissionsChangedAt = $date;

        return $this;
    }

    public function getPermissionsChangedAt()
    {
        return $this->permissionsChangedAt;
    }

    public function getGroupSections()
    {
        return $this->groupSections;
    }

    public function addGroupSection(GroupSection $groupSection)
    {
        $this->groupSections[] = $groupSection;
        $groupSection->setGroup($this);

        return $this;
    }

    public function removeGroupSection(GroupSection $groupSection)
    {
        $this->groupSections->removeElement($groupSection);

        return $this;
    }

    public function setGroupSections($groupSection)
    {
        $this->groupSections = $groupSection;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocationName()
    {
        return $this->locationName;
    }

    /**
     * @param string $locationName
     *
     * @return $this
     */
    public function setLocationName($locationName)
    {
        $this->locationName = $locationName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return Group
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Group $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function isLocalGroup()
    {
        return $this->groupType === self::GROUP_TYPE_LOCAL;
    }

    public function isCountryGroup()
    {
        return $this->groupType === self::GROUP_TYPE_COUNTRY;
    }

    public function isStateGroup()
    {
        return $this->groupType === self::GROUP_TYPE_STATE;
    }

    /**
     * Set transparency
     *
     * @param string $transparency
     * @return Group
     */
    public function setTransparency($transparency)
    {
        $this->transparency = $transparency;
    
        return $this;
    }

    /**
     * Get transparency
     *
     * @return string 
     */
    public function getTransparency()
    {
        return $this->transparency;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return Group
     */
    public function setSlug($slug)
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
    public function getUserRole()
    {
        if ($this->users->count()) {
            return new UserRole($this->users->first());
        }

        return null;
    }

    public function getOfficialTitle()
    {
        return $this->getOfficialName();
    }
}

<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Representative.
 *
 * @ORM\Table(
 *     name="representatives",
 *     indexes={
 *         @ORM\Index(name="is_nonlegislative", columns={"is_nonlegislative"}),
 *         @ORM\Index(name="rep_officialTitle_ind", columns={"officialTitle"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"user_id", "local_group"})}
 * )
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\RepresentativeRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Representative implements CheckingLimits
{
    const DEFAULT_AVATAR = '/bundles/civixfront/img/default_representative.png';

    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities", "api-poll", "api-representatives-list", "api-info",
     *      "api-search", "api-poll-public"})
     */
    private $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="representatives")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="localRepresentatives", cascade="persist")
     * @ORM\JoinColumn(name="local_group", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $localGroup;

    /**
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes={"image/png", "image/jpeg", "image/jpg"},
     *     groups={"profile"}
     * )
     * @Vich\UploadableField(mapping="avatar_image", fileNameProperty="avatarFileName")
     *
     * @var UploadedFile
     */
    private $avatar;

    /**
     * @ORM\Column(name="avatar_file_name", type="string", nullable=true)
     *
     * @var string
     */
    private $avatarFileName;

    /**
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes={"image/png", "image/jpeg", "image/jpg"},
     *     groups={"profile"}
     * )
     * @Vich\UploadableField(mapping="avatar_source_image", fileNameProperty="avatarSourceFileName")
     *
     * @var UploadedFile
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
     * @ORM\Column(name="officialTitle", type="string", length=255)
     * @Assert\NotBlank(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getOfficialTitle")
     * @Serializer\Groups({"api-activities", "api-representatives-list", "api-poll", "api-info",
     *      "api-search", "api-poll-public"})
     */
    private $officialTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=2)
     * @Assert\NotBlank(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $country;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\State", cascade="persist")
     * @ORM\JoinColumn(name="state", referencedColumnName="code", nullable=true, onDelete="SET NULL")
     * @Assert\NotBlank(groups={"registration"})
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     * @Assert\NotBlank(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getCity")
     * @Serializer\Groups({"api-info"})
     */
    private $city;

    /**
     * @var string
     * @ORM\Column(name="address1", type="string", length=255, nullable=true)
     */
    private $addressLine1;

    /**
     * @var string
     * @ORM\Column(name="address2", type="string", length=255, nullable=true)
     */
    private $addressLine2;

    /**
     * @var string
     * @ORM\Column(name="address3", type="string", length=255, nullable=true)
     */
    private $addressLine3;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=15)
     * @Assert\NotBlank(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank(groups={"registration"})
     */
    private $privatePhone;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @ORM\Column(name="email", type="string")
     * @Assert\NotBlank(groups={"registration"})
     * @Assert\Email(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $email;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank(groups={"registration"})
     * @Assert\Email(groups={"registration"})
     */
    private $privateEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=15, nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getFax")
     * @Serializer\Groups({"api-info"})
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255, nullable=true)
     *
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getWebsite")
     * @Serializer\Groups({"api-info"})
     */
    private $website;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\District", cascade="persist")
     * @ORM\JoinColumn(name="district_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $district;

    /**
     * @var string
     *
     * @ORM\Column(name="party", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $party;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     * @Serializer\Type("DateTime<'m/d/Y'>")
     */
    private $birthday;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_term", type="date", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     * @Serializer\Type("DateTime<'m/d/Y'>")
     */
    private $startTerm;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_term", type="date", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     * @Serializer\Type("DateTime<'m/d/Y'>")
     */
    private $endTerm;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $facebook;

    /**
     * @var string
     *
     * @ORM\Column(name="youtube", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $youtube;

    /**
     * @var string
     *
     * @ORM\Column(name="twitter", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $twitter;

    /**
     * @var string
     *
     * @ORM\Column(name="openstate_id", type="string", length=255, nullable=true)
     */
    private $openstateId;

    /**
     * @ORM\Column(name="cicero_id", type="integer", nullable=true, unique=true)
     *
     * @var int
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities", "api-poll", "api-search", "api-info", "api-poll-public"})
     */
    private $ciceroId;

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
     * @ORM\Column(name="is_nonlegislative", type="boolean", nullable=true)
     *
     * @var int
     */
    private $isNonLegislative;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->setCountry('US');
        $this->setStatus(self::STATUS_PENDING);
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Representative
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get fax.
     *
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Set fax.
     *
     * @param string $fax
     * @return $this
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get website.
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set website.
     *
     * @param string $website
     * @return $this
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get address1.
     *
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->addressLine1;
    }

    /**
     * Set address1.
     *
     * @param string $address1
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setAddressLine1($address1)
    {
        $this->addressLine1 = $address1;

        return $this;
    }

    /**
     * Get address2.
     *
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->addressLine2;
    }

    /**
     * Set address1.
     *
     * @param string $address2
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setAddressLine2($address2)
    {
        $this->addressLine2 = $address2;

        return $this;
    }

    /**
     * Get address3.
     *
     * @return string
     */
    public function getAddressLine3()
    {
        return $this->addressLine3;
    }

    /**
     * Set address1.
     *
     * @param string $address3
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setAddressLine3($address3)
    {
        $this->addressLine3 = $address3;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getOfficialTitle()
    {
        return $this->officialTitle;
    }

    /**
     * Set officialTitle.
     *
     * @param string $officialTitle
     *
     * @return Representative
     */
    public function setOfficialTitle($officialTitle)
    {
        $this->officialTitle = $officialTitle;

        return $this;
    }

    /**
     * Set country of address.
     *
     * @param string $country
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country of address.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set state of country.
     *
     * @param string $state
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state of country.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return null|string
     * @Serializer\VirtualProperty()
     * @Serializer\Type("string")
     * @Serializer\Groups({"api-info"})
     * @Serializer\SerializedName("state")
     */
    public function getStateCode()
    {
        if ($this->state instanceof State) {
            return $this->state->getCode();
        }

        return null;
    }

    /**
     * Set city.
     *
     * @param string $city
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Get officialAddress.
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-info"})
     *
     * @return string
     */
    public function getAddress()
    {
        $address = '';
        $address .= $this->addressLine1 ? $this->addressLine1 : '';
        $address .= $this->addressLine2 ? ' '.$this->addressLine2 : '';
        $address .= $this->addressLine3 ? ' '.$this->addressLine3 : '';

        return $address;
    }

    /**
     * Set officialPhone.
     *
     * @param string $phone
     *
     * @return Representative
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get officialPhone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getPrivatePhone()
    {
        return $this->privatePhone;
    }

    /**
     * @param string $privatePhone
     * @return Representative
     */
    public function setPrivatePhone($privatePhone)
    {
        $this->privatePhone = $privatePhone;

        return $this;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return Representative
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Representative
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrivateEmail()
    {
        return $this->privateEmail;
    }

    /**
     * @param mixed $privateEmail
     * @return Representative
     */
    public function setPrivateEmail($privateEmail)
    {
        $this->privateEmail = $privateEmail;

        return $this;
    }

    /**
     * Set avatar.
     *
     * @param UploadedFile $avatar
     *
     * @return Representative
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
     * @return Representative
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
     * Set avatarSourceFileName.
     *
     * @param string $avatarSourceFileName
     *
     * @return Representative
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
     * Set avatarSource.
     *
     * @param string $avatarSource
     *
     * @return Representative
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
     * Get DistrictId.
     *
     * @return int
     */
    public function getDistrictId()
    {
        $district = $this->getDistrict();

        return $district ? $district->getId() : null;
    }

    /**
     * Set party.
     *
     * @param string $party
     *
     * @return Representative
     */
    public function setParty($party)
    {
        $this->party = $party;

        return $this;
    }

    /**
     * Get party.
     *
     * @return string
     */
    public function getParty()
    {
        return $this->party;
    }

    /**
     * Set birthday.
     *
     * @param \DateTime $birthday
     *
     * @return Representative
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday.
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set startTerm.
     *
     * @param \DateTime $startTerm
     *
     * @return Representative
     */
    public function setStartTerm($startTerm)
    {
        $this->startTerm = $startTerm;

        return $this;
    }

    /**
     * Get startTerm.
     *
     * @return \DateTime
     */
    public function getStartTerm()
    {
        return $this->startTerm;
    }

    /**
     * Set endTerm.
     *
     * @param \DateTime $endTerm
     *
     * @return Representative
     */
    public function setEndTerm($endTerm)
    {
        $this->endTerm = $endTerm;

        return $this;
    }

    /**
     * Get endTerm.
     *
     * @return \DateTime
     */
    public function getEndTerm()
    {
        return $this->endTerm;
    }

    /**
     * Set facebook.
     *
     * @param string $facebook
     *
     * @return Representative
     */
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;

        return $this;
    }

    /**
     * Get facebook.
     *
     * @return string
     */
    public function getFacebook()
    {
        return $this->facebook;
    }

    /**
     * Set youtube.
     *
     * @param string $youtube
     *
     * @return Representative
     */
    public function setYoutube($youtube)
    {
        $this->youtube = $youtube;

        return $this;
    }

    /**
     * Get youtube.
     *
     * @return string
     */
    public function getYoutube()
    {
        return $this->youtube;
    }

    /**
     * Set twitter.
     *
     * @param string $twitter
     *
     * @return Representative
     */
    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;

        return $this;
    }

    /**
     * Get twitter.
     *
     * @return string
     */
    public function getTwitter()
    {
        return $this->twitter;
    }

    /**
     * Set openstateId.
     *
     * @param string $openstateId
     *
     * @return Representative
     */
    public function setOpenstateId($openstateId)
    {
        $this->openstateId = $openstateId;

        return $this;
    }

    /**
     * Get openstateId.
     *
     * @return string
     */
    public function getOpenstateId()
    {
        return $this->openstateId;
    }

    /**
     * Get CiceroId.
     *
     * @return int
     */
    public function getCiceroId()
    {
        return $this->ciceroId;
    }

    /**
     * Set CiceroId.
     *
     * @param int $ciceroId
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setCiceroId($ciceroId)
    {
        $this->ciceroId = $ciceroId;

        return $this;
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
     * @param $limit
     * @return Representative
     */
    public function setQuestionLimit($limit)
    {
        $this->questionLimit = $limit;

        return $this;
    }

    /**
     * Get Non-Legislative District relation.
     *
     * @return int
     */
    public function getIsNonLegislative()
    {
        return $this->isNonLegislative;
    }

    /**
     * Set Non-Legislative District relation.
     *
     * @param bool $isNonLegislative
     * @return Representative
     */
    public function setIsNonLegislative($isNonLegislative)
    {
        $this->isNonLegislative = $isNonLegislative;

        return $this;
    }

    public function __toString()
    {
        return $this->officialTitle;
    }

    /**
     * Check is representative in local district.
     *
     * @return bool
     */
    public function isLocalLeader()
    {
        return in_array($this->getDistrict()->getDistrictType(), array(District::LOCAL, District::LOCAL_EXEC));
    }

    /**
     * Set localGroup.
     *
     * @param \Civix\CoreBundle\Entity\Group $localGroup
     *
     * @return Representative
     */
    public function setLocalGroup(Group $localGroup = null)
    {
        $this->localGroup = $localGroup;

        return $this;
    }

    /**
     * Get localGroup.
     *
     * @return \Civix\CoreBundle\Entity\Group
     */
    public function getLocalGroup()
    {
        return $this->localGroup;
    }

    /**
     * Check if representative can to admin local group.
     * 
     * @return bool
     */
    public function isLocalAdmin()
    {
        return $this->getLocalGroup() instanceof Group;
    }

    /**
     * Set district.
     *
     * @param District $district
     *
     * @return Representative
     */
    public function setDistrict(District $district = null)
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district.
     *
     * @return District
     */
    public function getDistrict()
    {
        return $this->district;
    }

    public function getAddressArray()
    {
        return [
            'city' => $this->getCity(),
            'line1' => $this->getAddressLine1(),
            'line2' => $this->getAddressLine2(),
            'line3' => $this->getAddressLine3(),
            'state' => $this->getState(),
            'postal_code' => '',
            'country_code' => $this->getCountry(),
        ];
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
     * @return Representative
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}

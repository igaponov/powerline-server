<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Serializer\Type\Avatar;
use Civix\CoreBundle\Model\CropAvatarInterface;

/**
 * Representative.
 *
 * @ORM\Table(
 *      name="representatives",
 *      indexes={
 *          @ORM\Index(name="is_nonlegislative", columns={"is_nonlegislative"}),
 *          @ORM\Index(name="rep_firstName_ind", columns={"firstName"}),
 *          @ORM\Index(name="rep_lastName_ind", columns={"lastName"}),
 *          @ORM\Index(name="rep_officialTitle_ind", columns={"officialTitle"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\RepresentativeRepository")
 * @UniqueEntity(fields={"username"}, groups={"registration"})
 * @Vich\Uploadable
 * @Serializer\ExclusionPolicy("all")
 */
class Representative implements UserInterface, \Serializable, CheckingLimits, CropAvatarInterface, LeaderInterface
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
     * @Serializer\Expose()
     * @Serializer\ReadOnly()
     * @Serializer\Groups({"api-activities", "api-poll", "api-search", "api-poll-public"})
     */
    private $type = 'representative';

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255)
     * @Assert\NotBlank(groups={"registration", "profile"})
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getFirstName")
     * @Serializer\Groups({"api-activities", "api-representatives-list", "api-poll", "api-info",
     *      "api-search", "api-poll-public"})
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255)
     * @Assert\NotBlank(groups={"registration", "profile"})
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getLastName")
     * @Serializer\Groups({"api-activities", "api-representatives-list", "api-poll", "api-info",
     *     "api-search", "api-poll-public"})
     */
    private $lastName;

    /**
     * @ORM\Column(name="username", type="string", length=255, nullable=true, unique=true)
     *
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(name="salt", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $salt;

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
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities", "api-poll", "api-representatives-list", "api-info",
     *      "api-search", "api-poll-public"})
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
     * @ORM\Column(name="avatar_src", type="string", length=255, nullable=true)
     */
    private $avatarSrc;

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
     * @Serializer\Expose()
     * @Serializer\Type("string")
     * @Serializer\Accessor(getter="getStateCode")
     * @Serializer\Groups({"api-info"})
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
     * @ORM\Column(name="officialPhone", type="string", length=15)
     * @Assert\NotBlank(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getOfficialPhone")
     * @Serializer\Groups({"api-info"})
     */
    private $officialPhone;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @ORM\Column(name="email", type="string", length=50)
     * @Assert\NotBlank(groups={"registration"})
     * @Assert\Email(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Accessor(getter="getEmail")
     * @Serializer\Groups({"api-info"})
     */
    private $email;

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
     * @ORM\Column(name="is_nonlegislative", type="integer", nullable=true)
     *
     * @var int
     */
    private $isNonLegislative;

    /**
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="localRepresentatives", cascade="persist")
     * @ORM\JoinColumn(name="local_group", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $localGroup;

    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({"api-session"})
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;
    
    public function __construct()
    {
        $this->setCountry('US');
        $this->setStatus(self::STATUS_PENDING);
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->setUpdatedAt(new \DateTime());
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
     */
    public function getAvatarSrc()
    {
        return new Avatar($this);
    }

    /**
     * @param string $avatarSrc
     * @return Representative
     */
    public function setAvatarSrc($avatarSrc)
    {
        $this->avatarSrc = $avatarSrc;

        return $this;
    }

    /**
     * Serializes the representative.
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
     * Unserializes the representative.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->id
            ) = unserialize($serialized);
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
     * Get name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
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
     * Set name.
     *
     * @param string $name
     *
     * @return Representative
     */
    public function setFirstName($name)
    {
        $this->firstName = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Representative
     */
    public function setLastName($name)
    {
        $this->lastName = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return Representative
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return Representative
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Get user Roles.
     *
     * @return array
     */
    public function getRoles()
    {
        return array('ROLE_REPRESENTATIVE');
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
     * Get username.
     *
     * @return string
     */
    public function getOfficialName()
    {
        return $this->getFirstName().' '.$this->getLastName();
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

    public function getOfficialState()
    {
        return $this->state;
    }

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

    public function getOfficialCity()
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
    public function getOfficialAddress()
    {
        $address = '';
        $address .= $this->addressLine1 ? $this->addressLine1 : '';
        $address .= $this->addressLine2 ? ' '.$this->addressLine2 : '';
        $address .= $this->addressLine3 ? ' '.$this->addressLine3 : '';

        return $address;
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
     * Set officialPhone.
     *
     * @param string $officialPhone
     *
     * @return Representative
     */
    public function setOfficialPhone($officialPhone)
    {
        $this->officialPhone = $officialPhone;

        return $this;
    }

    /**
     * Get officialPhone.
     *
     * @return string
     */
    public function getOfficialPhone()
    {
        return $this->officialPhone;
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
     * Erase credentials.
     */
    public function eraseCredentials()
    {
    }

    /**
     * Compare users.
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public function equals(UserInterface $user)
    {
        return md5($this->getUsername()) == md5($user->getUsername());
    }

    /**
     * Set salt.
     *
     * @param string $salt
     *
     * @return Representative
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

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
     * Set avatarFilePath.
     *
     * @param string $avatarFilePath
     *
     * @return \Civix\CoreBundle\Entity\Representative
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
     * @param integer $limit
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
        return $this->firstName.' '.$this->lastName.' ('.$this->officialTitle.')';
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
            'city' => $this->getOfficialCity(),
            'line1' => $this->getOfficialAddress(),
            'line2' => '',
            'state' => $this->getOfficialState(),
            'postal_code' => '',
            'country_code' => 'US',
        ];
    }
    

    /**
     * Set token.
     *
     * @param string $token
     *
     * @return Representative
     */
    public function setToken($token)
    {
    	$this->token = $token;
    
    	return $this;
    }
    
    /**
     * Get token.
     *
     * @return string
     */
    public function getToken()
    {
    	return $this->token;
    }
    
    public function generateToken()
    {
    	$bytes = false;
    	if (function_exists('openssl_random_pseudo_bytes') && 0 !== stripos(PHP_OS, 'win')) {
    		$bytes = openssl_random_pseudo_bytes(32, $strong);
    
    		if (true !== $strong) {
    			$bytes = false;
    		}
    	}
    
    	if (false === $bytes) {
    		$bytes = hash('sha256', uniqid(mt_rand(), true), true);
    	}
    
    	$this->setToken(base_convert(bin2hex($bytes), 16, 36).$this->getId());
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

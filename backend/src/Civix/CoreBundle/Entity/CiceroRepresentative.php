<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Civix\CoreBundle\Serializer\Type\Avatar;

/**
 * RepresentativeStorage.
 *
 * @ORM\Table(name="cicero_representatives", indexes={
 *      @ORM\Index(name="repst_firstName_ind", columns={"firstName"}),
 *      @ORM\Index(name="repst_lastName_ind", columns={"lastName"}),
 *      @ORM\Index(name="repst_officialTitle_ind", columns={"officialTitle"})
 * })
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\CiceroRepresentativeRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable()
 * @Serializer\ExclusionPolicy("all")
 */
class CiceroRepresentative implements HasAvatarInterface
{
    const DEFAULT_AVATAR = '/bundles/civixfront/img/default_representative.png';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @Serializer\Expose()
     * @Serializer\Groups({"api-representatives-list", "api-info", "api-search"})
     * @Serializer\Since("2")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="firstName", type="string", length=255)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-representatives-list", "api-info", "api-search"})
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="lastName", type="string", length=255)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-representatives-list", "api-info", "api-search"})
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="officialTitle", type="string", length=255)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-representatives-list", "api-info", "api-search"})
     */
    private $officialTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=15, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=15, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=80, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     * @Serializer\SerializedName("email")
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $website;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=2, nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\State", inversedBy="stRepresentatives", cascade="persist")
     * @ORM\JoinColumn(name="state", referencedColumnName="code", nullable=true, onDelete="SET NULL")
     * @Serializer\Expose()
     * @Serializer\Type("string")
     * @Serializer\Accessor(getter="getStateCode")
     * @Serializer\Groups({"api-info"})
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     * @Serializer\Expose()
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
     * @ORM\Column(name="avatar_source_file_name", type="string", length=255)
     */
    private $avatarSourceFileName;

    /**
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes={"image/png", "image/jpeg", "image/jpg"}
     * )
     * @Vich\UploadableField(mapping="avatar_representative", fileNameProperty="avatarFileName")
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
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\District", cascade="persist")
     * @ORM\JoinColumn(name="district_id", referencedColumnName="id", onDelete="cascade")
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
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * Set storageId.
     *
     * @param int $id
     *
     * @return CiceroRepresentative
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    public function getAddress()
    {
        $address = '';
        $address .= $this->addressLine1 ? $this->addressLine1 : '';
        $address .= $this->addressLine2 ? ' '.$this->addressLine2 : '';
        $address .= $this->addressLine3 ? ' '.$this->addressLine3 : '';

        return $address;
    }

    /**
     * Set firstName.
     *
     * @param string $firstName
     *
     * @return CiceroRepresentative
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName.
     *
     * @param string $lastName
     *
     * @return CiceroRepresentative
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Get full name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * Set officialTitle.
     *
     * @param string $officialTitle
     *
     * @return CiceroRepresentative
     */
    public function setOfficialTitle($officialTitle)
    {
        $this->officialTitle = $officialTitle;

        return $this;
    }

    /**
     * Get officialTitle.
     *
     * @return string
     */
    public function getOfficialTitle()
    {
        return $this->officialTitle;
    }

    /**
     * Get phone number.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set phone number.
     *
     * @param string $phone
     *
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get fax number.
     *
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Set fax number.
     *
     * @param string $fax
     *
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
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
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get website url.
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set website url.
     *
     * @param string $url
     *
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function setWebsite($url)
    {
        $this->website = $url;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
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
     * Set state.
     *
     * @param State $state
     *
     * @return CiceroRepresentative
     */
    public function setState(State $state = null)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Set city.
     *
     * @param string $city
     *
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function setCity($city)
    {
        $this->city = $city;

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
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
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
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
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
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function setAddressLine3($address3)
    {
        $this->addressLine3 = $address3;

        return $this;
    }

    /**
     * Get districtId.
     *
     * @return int
     */
    public function getDistrictId()
    {
        return $this->getDistrict()->getId();
    }

    /**
     * Get district type name by district type id.
     *
     * @return string
     */
    public function getDistrictTypeName()
    {
        return $this->getDistrict()->getDistrictTypeName();
    }

    public function setAvatarSourceFileName($avatarSourceFileName)
    {
        $this->avatarSourceFileName = $avatarSourceFileName;

        return $this;
    }

    public function getAvatarSourceFileName()
    {
        return $this->avatarSourceFileName;
    }

    /**
     * Set avatar.
     *
     * @param UploadedFile $avatar
     *
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * Get avatarPath.
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-representatives-list", "api-info", "api-search"})
     * @Serializer\Type("Avatar")
     * @Serializer\SerializedName("avatar_file_path")
     * @return Avatar
     */
    public function getAvatarFilePath()
    {
        return new Avatar($this);
    }

    public function isLocalLeader()
    {
        return in_array($this->getDistrict()->getDistrictType(), array(District::LOCAL, District::LOCAL_EXEC));
    }

    /**
     * Set district.
     *
     * @param District $district
     *
     * @return CiceroRepresentative
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

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("type")
     * @Serializer\Groups({"api-search"})
     */
    public function getType()
    {
        return 'representative';
    }
    /**
     * Set party.
     *
     * @param string $party
     *
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * @return CiceroRepresentative
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
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return CiceroRepresentative
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function setCurrentTimeAsUpdatedAt()
    {
        $this->setUpdatedAt(new \DateTime('now'));
    }

    public function getStateCode()
    {
        if ($this->state instanceof State) {
            return $this->state->getCode();
        }

        return null;
    }

    /**
     * @return int
     * @deprecated For compatibility with v.1
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-representatives-list", "api-info", "api-search"})
     * @Serializer\Until("1")
     * @Serializer\SerializedName("storage_id")
     */
    public function getStorageId()
    {
        return $this->id;
    }
}

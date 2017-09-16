<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Model\Avatar\DefaultAvatarInterface;
use Civix\CoreBundle\Model\Avatar\FirstLetterDefaultAvatar;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
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
 * @Serializer\ExclusionPolicy("all")
 */
class CiceroRepresentative implements HasAvatarInterface, ChangeableAvatarInterface
{
    use HasAvatarTrait;

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
     * @var State
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
     * @var string
     *
     * @ORM\Column()
     */
    private $bioguide;

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
    public function setId($id): CiceroRepresentative
    {
        $this->id = $id;

        return $this;
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

    public function getAddress(): string
    {
        $address = '';
        $address .= $this->addressLine1 ? : '';
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
    public function setFirstName(string $firstName): CiceroRepresentative
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName(): ?string
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
    public function setLastName(string $lastName): CiceroRepresentative
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName.
     *
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Get full name
     *
     * @return string
     */
    public function getFullName(): string
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
    public function setOfficialTitle($officialTitle): CiceroRepresentative
    {
        $this->officialTitle = $officialTitle;

        return $this;
    }

    /**
     * Get officialTitle.
     *
     * @return string
     */
    public function getOfficialTitle(): ?string
    {
        return $this->officialTitle;
    }

    /**
     * Get phone number.
     *
     * @return string
     */
    public function getPhone(): ?string
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
    public function setPhone(string $phone): CiceroRepresentative
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get fax number.
     *
     * @return string
     */
    public function getFax(): ?string
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
    public function setFax(string $fax): CiceroRepresentative
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail(): ?string
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
    public function setEmail(string $email): CiceroRepresentative
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get website url.
     *
     * @return string
     */
    public function getWebsite(): ?string
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
    public function setWebsite(string $url): CiceroRepresentative
    {
        $this->website = $url;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry(): ?string
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
    public function setCountry(string $country): CiceroRepresentative
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get state.
     *
     * @return string
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity(): ?string
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
    public function setState(State $state = null): CiceroRepresentative
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
    public function setCity(string $city): CiceroRepresentative
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get address1.
     *
     * @return string
     */
    public function getAddressLine1(): ?string
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
    public function setAddressLine1(string $address1): CiceroRepresentative
    {
        $this->addressLine1 = $address1;

        return $this;
    }

    /**
     * Get address2.
     *
     * @return string
     */
    public function getAddressLine2(): ?string
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
    public function setAddressLine2(string $address2): CiceroRepresentative
    {
        $this->addressLine2 = $address2;

        return $this;
    }

    /**
     * Get address3.
     *
     * @return string
     */
    public function getAddressLine3(): ?string
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
    public function setAddressLine3(string $address3): CiceroRepresentative
    {
        $this->addressLine3 = $address3;

        return $this;
    }

    /**
     * Get districtId.
     *
     * @return int
     */
    public function getDistrictId(): ?int
    {
        $district = $this->getDistrict();

        return $district ? $district->getId() : null;
    }

    /**
     * Get district type name by district type id.
     *
     * @return string
     */
    public function getDistrictTypeName(): ?string
    {
        $district = $this->getDistrict();

        return $district ? $district->getDistrictTypeName() : '';
    }

    /**
     * Get default avatar.
     *
     * @return DefaultAvatarInterface
     */
    public function getDefaultAvatar(): DefaultAvatarInterface
    {
        return new FirstLetterDefaultAvatar($this->firstName);
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
    public function getAvatarFilePath(): Avatar
    {
        return new Avatar($this);
    }

    public function isLocalLeader(): bool
    {
        $district = $this->getDistrict();

        return in_array($district ? $district->getDistrictType() : -1, array(District::LOCAL, District::LOCAL_EXEC), true);
    }

    /**
     * Set district.
     *
     * @param District $district
     *
     * @return CiceroRepresentative
     */
    public function setDistrict(District $district = null): CiceroRepresentative
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district.
     *
     * @return District
     */
    public function getDistrict(): ?District
    {
        return $this->district;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("type")
     * @Serializer\Groups({"api-search"})
     */
    public function getType(): string
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
    public function setParty(string $party): CiceroRepresentative
    {
        $this->party = $party;

        return $this;
    }

    /**
     * Get party.
     *
     * @return string
     */
    public function getParty(): ?string
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
    public function setBirthday(\DateTime $birthday): CiceroRepresentative
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday.
     *
     * @return \DateTime
     */
    public function getBirthday(): ?\DateTime
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
    public function setStartTerm(\DateTime $startTerm): CiceroRepresentative
    {
        $this->startTerm = $startTerm;

        return $this;
    }

    /**
     * Get startTerm.
     *
     * @return \DateTime
     */
    public function getStartTerm(): ?\DateTime
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
    public function setEndTerm(\DateTime $endTerm): CiceroRepresentative
    {
        $this->endTerm = $endTerm;

        return $this;
    }

    /**
     * Get endTerm.
     *
     * @return \DateTime
     */
    public function getEndTerm(): ?\DateTime
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
    public function setFacebook(string $facebook): CiceroRepresentative
    {
        $this->facebook = $facebook;

        return $this;
    }

    /**
     * Get facebook.
     *
     * @return string
     */
    public function getFacebook(): ?string
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
    public function setYoutube(string $youtube): CiceroRepresentative
    {
        $this->youtube = $youtube;

        return $this;
    }

    /**
     * Get youtube.
     *
     * @return string
     */
    public function getYoutube(): ?string
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
    public function setTwitter(string $twitter): CiceroRepresentative
    {
        $this->twitter = $twitter;

        return $this;
    }

    /**
     * Get twitter.
     *
     * @return string
     */
    public function getTwitter(): ?string
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
    public function setOpenstateId(string $openstateId): CiceroRepresentative
    {
        $this->openstateId = $openstateId;

        return $this;
    }

    /**
     * Get openstateId.
     *
     * @return string
     */
    public function getOpenstateId(): ?string
    {
        return $this->openstateId;
    }

    /**
     * @return string
     */
    public function getBioguide(): ?string
    {
        return $this->bioguide;
    }

    /**
     * @param string $bioguide
     * @return CiceroRepresentative
     */
    public function setBioguide(string $bioguide): CiceroRepresentative
    {
        $this->bioguide = $bioguide;

        return $this;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return CiceroRepresentative
     */
    public function setUpdatedAt(\DateTime $updatedAt): CiceroRepresentative
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function setCurrentTimeAsUpdatedAt(): void
    {
        $this->setUpdatedAt(new \DateTime('now'));
    }

    public function getStateCode(): ?string
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
    public function getStorageId(): ?int
    {
        return $this->id;
    }
}

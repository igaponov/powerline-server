<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Model\Avatar\DefaultAvatarInterface;
use Civix\CoreBundle\Model\Avatar\FirstLetterDefaultAvatar;
use Civix\CoreBundle\Serializer\Type\Avatar;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

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
class Representative implements CheckingLimits, LeaderContentRootInterface, HasAvatarInterface, ChangeableAvatarInterface
{
    use HasStripeAccountTrait,
        HasAvatarTrait;

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
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User", inversedBy="representatives")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="localRepresentatives", cascade="persist")
     * @ORM\JoinColumn(name="local_group", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @Assert\NotBlank(groups={"approve"})
     */
    private $localGroup;

    /**
     * @var string|null
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
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

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
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\State", cascade="persist")
     * @ORM\JoinColumn(name="state", referencedColumnName="code", nullable=true, onDelete="SET NULL")
     * @Assert\NotBlank(groups={"registration"})
     */
    private $state;

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
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status = self::STATUS_PENDING;

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
     * @var string
     *
     * @ORM\Column(name="email", type="string")
     * @Assert\NotBlank(groups={"registration"})
     * @Assert\Email(groups={"registration"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-info"})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank(groups={"registration"})
     * @Assert\Email(groups={"registration"})
     */
    private $privateEmail;

    /**
     * @var District
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\District", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $district;

    /**
     * @var CiceroRepresentative
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\CiceroRepresentative")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities", "api-poll", "api-search", "api-info", "api-poll-public"})
     */
    private $ciceroRepresentative;

    /**
     * @var int
     *
     * @Assert\Regex(
     *      pattern="/^\d+$/",
     *      message="The value cannot contain a non-numerical symbols"
     * )
     * @ORM\Column(name="questions_limit", type="integer", nullable=true)
     */
    private $questionLimit;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_nonlegislative", type="boolean")
     */
    private $isNonLegislative = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->setCountry('US');
        $this->setUpdatedAt(new \DateTime());
    }

    public function getType(): string
    {
        return 'representative';
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Representative
     */
    public function setUser(User $user): Representative
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get avatarSrc
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-activities", "api-poll", "api-representatives-list", "api-info",
     *      "api-search", "api-poll-public"})
     * @Serializer\Type("Avatar")
     * @Serializer\SerializedName("avatar_file_path")
     * @return Avatar
     */
    public function getAvatarFilePath(): Avatar
    {
        if ($this->getCiceroRepresentative() && !$this->getAvatarFileName()) {
            return new Avatar($this->getCiceroRepresentative());
        }
        return new Avatar($this);
    }

    /**
     * Get default avatar
     *
     * @return DefaultAvatarInterface
     */
    public function getDefaultAvatar(): DefaultAvatarInterface
    {
        return new FirstLetterDefaultAvatar($this->getFirstName());
    }

    /**
     * Get address1.
     *
     * @return string
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Set address1.
     *
     * @param string $address1
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setAddress(string $address1): Representative
    {
        $this->address = $address1;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getOfficialTitle(): ?string
    {
        if ($this->getCiceroRepresentative() && $this->getCiceroRepresentative()->getOfficialTitle()) {
            return $this->getCiceroRepresentative()->getOfficialTitle();
        }

        return $this->officialTitle;
    }

    /**
     * Set officialTitle.
     *
     * @param string $officialTitle
     *
     * @return Representative
     */
    public function setOfficialTitle(string $officialTitle): Representative
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
    public function setCountry(string $country): Representative
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country of address.
     *
     * @return string
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Set state of country.
     *
     * @param State $state
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setState(State $state): Representative
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state of country.
     *
     * @return State
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @return null|string
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("string")
     * @Serializer\Groups({"api-info"})
     * @Serializer\SerializedName("state")
     */
    public function getStateCode(): ?string
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
    public function setCity(string $city): Representative
    {
        $this->city = $city;

        return $this;
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
     * Set officialPhone.
     *
     * @param string $phone
     *
     * @return Representative
     */
    public function setPhone(string $phone): Representative
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get officialPhone.
     *
     * @return string
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getPrivatePhone(): ?string
    {
        return $this->privatePhone;
    }

    /**
     * @param string $privatePhone
     * @return Representative
     */
    public function setPrivatePhone(string $privatePhone): Representative
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
    public function setStatus(int $status): Representative
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
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
     * @return Representative
     */
    public function setEmail(string $email): Representative
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrivateEmail(): ?string
    {
        return $this->privateEmail;
    }

    /**
     * @param mixed $privateEmail
     * @return Representative
     */
    public function setPrivateEmail(string $privateEmail): Representative
    {
        $this->privateEmail = $privateEmail;

        return $this;
    }

    /**
     * @return District
     */
    public function getDistrict(): ?District
    {
        return $this->district;
    }

    /**
     * @param District $district
     * @return Representative
     */
    public function setDistrict(District $district): Representative
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get Cicero representative.
     *
     * @return CiceroRepresentative
     */
    public function getCiceroRepresentative(): CiceroRepresentative
    {
        return $this->ciceroRepresentative;
    }

    /**
     * Set CiceroId.
     *
     * @param CiceroRepresentative $ciceroRepresentative
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function setCiceroRepresentative(CiceroRepresentative $ciceroRepresentative = null): Representative
    {
        $this->ciceroRepresentative = $ciceroRepresentative;

        return $this;
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
     * @param $limit
     * @return Representative
     */
    public function setQuestionLimit(?int $limit): Representative
    {
        $this->questionLimit = $limit;

        return $this;
    }

    /**
     * Get Non-Legislative District relation.
     *
     * @return int
     */
    public function getIsNonLegislative(): int
    {
        return $this->isNonLegislative;
    }

    /**
     * Set Non-Legislative District relation.
     *
     * @param bool $isNonLegislative
     * @return Representative
     */
    public function setIsNonLegislative($isNonLegislative): Representative
    {
        $this->isNonLegislative = $isNonLegislative;

        return $this;
    }

    public function __toString(): string
    {
        return (string)$this->officialTitle;
    }

    /**
     * Set localGroup.
     *
     * @param \Civix\CoreBundle\Entity\Group $localGroup
     *
     * @return Representative
     */
    public function setLocalGroup(Group $localGroup = null): Representative
    {
        $this->localGroup = $localGroup;

        return $this;
    }

    /**
     * Get localGroup.
     *
     * @return \Civix\CoreBundle\Entity\Group
     */
    public function getLocalGroup(): Group
    {
        return $this->localGroup;
    }

    /**
     * Check if representative can to admin local group.
     * 
     * @return bool
     */
    public function isLocalAdmin(): bool
    {
        return $this->getLocalGroup() instanceof Group;
    }

    public function getAddressArray(): array
    {
        return [
            'city' => $this->getCity(),
            'line1' => $this->getAddress(),
            'line2' => '',
            'state' => $this->getState(),
            'postal_code' => '',
            'country_code' => $this->getCountry(),
        ];
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Representative
     */
    public function setUpdatedAt(\DateTime $updatedAt): Representative
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     * @internal
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-representatives-list", "api-info"})
     */
    public function getFirstName(): ?string
    {
        return $this->user->getFirstName();
    }

    /**
     * @return string
     * @internal
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-representatives-list", "api-info"})
     */
    public function getLastName(): ?string
    {
        return $this->user->getLastName();
    }
}

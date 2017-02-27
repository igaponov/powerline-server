<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(name="discount_codes", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\DiscountCodeRepository")
 * @Serializer\ExclusionPolicy("ALL")
 */
class DiscountCode
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=12)
     * @Serializer\Expose()
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="original_code", type="string", nullable=true)
     */
    private $originalCode;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE", unique=true)
     */
    private $owner;

    /**
     * @var DiscountCodeUse[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Civix\CoreBundle\Entity\DiscountCodeUse", mappedBy="discountCode", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    private $uses;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Type("DateTime")
     */
    private $createdAt;

    public function __construct($originalCode = null, User $owner = null)
    {
        $this->code = $this->getRandomCode();
        $this->originalCode = $originalCode;
        $this->owner = $owner;
        $this->uses = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getOriginalCode(): string
    {
        return $this->originalCode;
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @return DiscountCodeUse[]|ArrayCollection
     */
    public function getUses(): ArrayCollection
    {
        return $this->uses;
    }

    /**
     * @return int
     */
    public function getUsesCount(): int
    {
        return $this->uses->count();
    }

    /**
     * @param User $user
     * @return DiscountCode
     */
    public function use(User $user): DiscountCode
    {
        $this->uses[] = new DiscountCodeUse($this, $user);

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Return random alphanumeric string
     *
     * @return string
     */
    private function getRandomCode(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ($i = 0; $i < 12; $i++) {
            $result .= $characters[random_int(0, 35)];
        }

        return $result;
    }
}
<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(name="discount_code_uses", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("ALL")
 */
class DiscountCodeUse
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DiscountCode
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\DiscountCode")
     * @ORM\JoinColumn(name="discount_code_id", nullable=false, onDelete="CASCADE")
     */
    private $discountCode;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct(DiscountCode $discountCode, User $user)
    {
        $this->discountCode = $discountCode;
        $this->user = $user;
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
     * @return DiscountCode
     */
    public function getDiscountCode(): DiscountCode
    {
        return $this->discountCode;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
<?php

namespace Civix\Component\Notification\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Device
 * @package Civix\CoreBundle\Entity\Notification
 *
 * @ORM\Entity()
 * @ORM\Table(name="notification_devices")
 * @UniqueEntity(fields={"id"})
 */
class Device implements ModelInterface
{
    const TYPE_IOS = 0;
    const TYPE_ANDROID = 1;

    /**
     * @var string
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     * @Assert\NotBlank()
     * @Assert\Uuid()
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column()
     * @Assert\NotBlank()
     */
    private $identifier;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     * @Assert\NotBlank()
     */
    private $timezone;

    /**
     * @var string
     *
     * @ORM\Column()
     * @Assert\NotBlank()
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column()
     * @Assert\NotBlank()
     */
    private $os;

    /**
     * @var string
     *
     * @ORM\Column()
     * @Assert\NotBlank()
     */
    private $model;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getTypes", strict=true)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var RecipientInterface
     *
     * @ORM\ManyToOne(targetEntity="Civix\Component\Notification\Model\RecipientInterface")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    public static function getTypes(): array
    {
        return [
            self::TYPE_IOS,
            self::TYPE_ANDROID,
        ];
    }

    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_IOS => 'ios',
            self::TYPE_ANDROID => 'android',
        ];
    }

    public function __construct(RecipientInterface $user)
    {
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Device
     */
    public function setId(?string $id): Device
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return Device
     */
    public function setIdentifier(?string $identifier): Device
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimezone(): ?int
    {
        return $this->timezone;
    }

    /**
     * @param int $timezone
     * @return Device
     */
    public function setTimezone(?int $timezone): Device
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return Device
     */
    public function setVersion(?string $version): Device
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getOs(): ?string
    {
        return $this->os;
    }

    /**
     * @param string $os
     * @return Device
     */
    public function setOs(?string $os): Device
    {
        $this->os = $os;

        return $this;
    }

    /**
     * @return int
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return Device
     */
    public function setType(?int $type): Device
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIos(): bool 
    {
        return $this->type === self::TYPE_IOS;
    }

    /**
     * @return bool
     */
    public function isAndroid(): bool 
    {
        return $this->type === self::TYPE_ANDROID;
    }

    /**
     * @return string
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * @param string $model
     * @return Device
     */
    public function setModel(?string $model): Device
    {
        $this->model = $model;

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
     * @return RecipientInterface
     */
    public function getUser(): RecipientInterface
    {
        return $this->user;
    }
}
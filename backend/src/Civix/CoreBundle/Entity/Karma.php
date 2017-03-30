<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="karma", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity()
 */
class Karma
{
    const TYPE_REPRESENTATIVE_SCREEN = 1;

    const TYPE_FOLLOW = 2;

    const TYPE_APPROVE_FOLLOW_REQUEST = 3;

    const TYPE_JOIN_GROUP = 4;

    const TYPE_CREATE_POST = 5;

    const TYPE_LOAD_TOWN_FILTER = 6;

    const TYPE_LOAD_STATE_FILTER = 7;

    const TYPE_LOAD_COUNTRY_FILTER = 8;

    const TYPE_ANSWER_POLL = 9;

    const TYPE_RECEIVE_UPVOTE_ON_POST = 10;

    const TYPE_RECEIVE_UPVOTE_ON_COMMENT = 11;

    const TYPE_VIEW_ANNOUNCEMENT = 12;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;
    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $points;
    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $type;
    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $metadata;
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public static function getTypes()
    {
        return [
            self::TYPE_REPRESENTATIVE_SCREEN,
            self::TYPE_FOLLOW,
            self::TYPE_APPROVE_FOLLOW_REQUEST,
            self::TYPE_JOIN_GROUP,
            self::TYPE_CREATE_POST,
            self::TYPE_LOAD_TOWN_FILTER,
            self::TYPE_LOAD_STATE_FILTER,
            self::TYPE_LOAD_COUNTRY_FILTER,
            self::TYPE_ANSWER_POLL,
            self::TYPE_RECEIVE_UPVOTE_ON_POST,
            self::TYPE_RECEIVE_UPVOTE_ON_COMMENT,
            self::TYPE_VIEW_ANNOUNCEMENT,
        ];
    }

    public function __construct(User $user, int $type, int $points, array $metadata = [])
    {
        if (!in_array($type, self::getTypes())) {
            throw new \OutOfBoundsException('Invalid karma\'s action type.');
        }
        if ($points <= 0) {
            throw new \LogicException('Points must be greater than 0.');
        }
        $this->user = $user;
        $this->type = $type;
        $this->points = $points;
        $this->createdAt = new \DateTime();
        $this->metadata = $metadata;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
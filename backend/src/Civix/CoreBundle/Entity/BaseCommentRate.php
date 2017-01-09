<?php
namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class BaseCommentRate
 * @package Civix\CoreBundle\Entity
 *
 * @method $this setComment(BaseComment $comment)
 * @method BaseComment getComment()
 */
class BaseCommentRate
{
    const RATE_UP = 1;
    const RATE_DOWN = -1;
    const RATE_DELETE = 0;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $user;

    /**
     * @ORM\Column(name="rate_value", type="smallint")
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getRateValues", strict=true)
     */
    protected $rateValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    public static function getRateValues()
    {
        return [
            self::RATE_UP,
            self::RATE_DOWN,
            self::RATE_DELETE,
        ];
    }

    public static function getRateValueLabels()
    {
        return [
            self::RATE_UP => 'up',
            self::RATE_DOWN => 'down',
            self::RATE_DELETE => 'delete',
        ];
    }

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
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
     * Set rateValue.
     *
     * @param int $rateValue
     *
     * @return $this
     */
    public function setRateValue($rateValue)
    {
        $this->rateValue = $rateValue;

        return $this;
    }

    /**
     * Get rateValue.
     *
     * @return int
     */
    public function getRateValue()
    {
        return $this->rateValue;
    }

    public function getRateValueLabel()
    {
        $labels = self::getRateValueLabels();
        if (isset($labels[$this->rateValue])) {
            return $labels[$this->rateValue];
        }

        return '';
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
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
}
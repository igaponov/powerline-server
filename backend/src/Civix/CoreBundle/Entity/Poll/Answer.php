<?php

namespace Civix\CoreBundle\Entity\Poll;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Answer entity.
 *
 * @ORM\Table(name="poll_answers")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Poll\AnswerRepository")
 * @ORM\HasLifecycleCallbacks
 * @Serializer\ExclusionPolicy("all")
 */
class Answer
{
    const PRIVACY_PUBLIC = 0;
    const PRIVACY_PRIVATE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-answer", "api-answers-list", "api-leader-answers", "activity-list"})
     */
    private $id;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-leader-answers"})
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Option", inversedBy="answers")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "activity-list"})
     */
    private $option;

    /**
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-answers-list"})
     */
    private $question;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-answer", "activity-list"})
     * @Assert\Length(max=500, groups={"api-poll"})
     */
    private $comment;

    /**
     * @var int
     *
     * @ORM\Column(name="payment_amount", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-leader-answers", "api-answer"})
     */
    private $paymentAmount;

    /**
     * @var int
     *
     * @ORM\Column(name="privacy", type="smallint")
     */
    private $privacy = self::PRIVACY_PUBLIC;

    /**
     * @var \DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-leader-answers", "api-answer"})
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    public static function getPrivacyTypes()
    {
        return [
            self::PRIVACY_PUBLIC,
            self::PRIVACY_PRIVATE,
        ];
    }

    public static function getPrivacyLabels()
    {
        return [
            self::PRIVACY_PUBLIC => 'public',
            self::PRIVACY_PRIVATE => 'private',
        ];
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
     * Set privacy.
     *
     * @param int $privacy
     *
     * @return Answer
     */
    public function setPrivacy($privacy)
    {
        $this->privacy = $privacy == self::PRIVACY_PRIVATE ? self::PRIVACY_PRIVATE : self::PRIVACY_PUBLIC;

        return $this;
    }

    /**
     * Get privacy.
     *
     * @return int
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api-info"})
     * @Serializer\SerializedName("user")
     */
    public function getUserInfo()
    {
        return $this->privacy === self::PRIVACY_PUBLIC ? $this->user : null;
    }
    /**
     * Set user.
     *
     * @param User $user
     *
     * @return Answer
     */
    public function setUser(User $user = null)
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
     * Set comment.
     *
     * @param string $comment
     *
     * @return Answer
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set question.
     *
     * @param Question $question
     *
     * @return Answer
     */
    public function setQuestion(Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set option.
     *
     * @param Option $option
     *
     * @return Answer
     */
    public function setOption(Option $option = null)
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Get option.
     *
     * @return Option
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param int $paymentAmount
     *
     * @return $this
     */
    public function setPaymentAmount($paymentAmount)
    {
        $this->paymentAmount = $paymentAmount;

        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentAmount()
    {
        return $this->paymentAmount;
    }

    /**
     * @Serializer\Groups({"api-poll", "api-answer"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("current_payment_amount")
     */
    public function getCurrentPaymentAmount()
    {
        return intval($this->getOption()->getIsUserAmount() ?
            $this->getPaymentAmount() :
            $this->getOption()->getPaymentAmount());
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCurrentTimeAsCreatedAt()
    {
        $this->setCreatedAt(new \DateTime('now'));
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Answer
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

    /**
     * @return int
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-answer", "api-answers-list", "api-leader-answers"})
     */
    public function getOptionId()
    {
        return $this->getOption()->getId();
    }
}

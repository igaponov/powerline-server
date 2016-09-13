<?php

namespace Civix\CoreBundle\Entity\Poll;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * Option entity.
 *
 * @ORM\Table(name="poll_options")
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class Option implements ContentInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-answer", "api-poll-public", "api-leader-poll"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=150)
     * @Assert\NotBlank()
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     */
    private $value;

    /**
     * @var int
     *
     * @ORM\Column(name="payment_amount", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-leader-poll"})
     */
    private $payment_amount;

    /**
     * @ORM\Column(name="is_user_amount", type="boolean", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     */
    private $isUserAmount;

    /**
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="options")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $question;

    /**
     * @ORM\OneToMany(targetEntity="Answer", mappedBy="option", orphanRemoval=true)
     */
    private $answers;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

    /**
     * Get votes count.
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-poll"})
     * @return int
     */
    public function getVotesCount()
    {
        return $this->getAnswers()->count();
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
     * Set value.
     *
     * @param string $value
     *
     * @return Option
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set question.
     *
     * @param Question $question
     *
     * @return Option
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
     * Add answers.
     *
     * @param Answer $answers
     *
     * @return Option
     */
    public function addAnswer(Answer $answers)
    {
        $this->answers[] = $answers;

        return $this;
    }

    /**
     * Remove answers.
     *
     * @param Answer $answers
     */
    public function removeAnswer(Answer $answers)
    {
        $this->answers->removeElement($answers);
    }

    /**
     * Get answers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param float $payment_amount
     *
     * @return $this
     */
    public function setPaymentAmount($payment_amount)
    {
        $this->payment_amount = $payment_amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentAmount()
    {
        return $this->payment_amount;
    }

    /**
     * @param mixed $isUserAmount
     *
     * @return $this
     */
    public function setIsUserAmount($isUserAmount)
    {
        $this->isUserAmount = $isUserAmount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsUserAmount()
    {
        return $this->isUserAmount;
    }
}

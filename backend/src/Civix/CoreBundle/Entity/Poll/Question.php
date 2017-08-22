<?php

namespace Civix\CoreBundle\Entity\Poll;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\HashTaggableInterface;
use Civix\CoreBundle\Entity\HashTaggableTrait;
use Civix\CoreBundle\Entity\LeaderContentInterface;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\SubscriptionInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Civix\CoreBundle\Model\Group\GroupSectionTrait;
use Civix\CoreBundle\Validator\Constraints\PublishDate;
use Civix\CoreBundle\Validator\Constraints\PublishedPollAmount;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Serializer\Type\Image;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * Question entity.
 *
 * @ORM\Table(name="poll_questions")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Poll\QuestionRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(name="hashTags",
 *          joinTable=@ORM\JoinTable(name="hash_tags_questions")
 *      ),
 *      @ORM\AssociationOverride(name="groupSections",
 *          joinTable=@ORM\JoinTable(name="poll_sections",
 *              inverseJoinColumns={@ORM\JoinColumn(name="group_section_id", onDelete="CASCADE")}
 *          )
 *      )
 * })
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "group" = "Civix\CoreBundle\Entity\Poll\Question\Group",
 *      "representative" = "Civix\CoreBundle\Entity\Poll\Question\Representative",
 *      "superuser" = "Civix\CoreBundle\Entity\Poll\Question\Superuser",
 *      "representative_news" = "Civix\CoreBundle\Entity\Poll\Question\RepresentativeNews",
 *      "group_news" = "Civix\CoreBundle\Entity\Poll\Question\GroupNews",
 *      "representative_petition" = "Civix\CoreBundle\Entity\Poll\Question\RepresentativePetition",
 *      "group_petition" = "Civix\CoreBundle\Entity\Poll\Question\GroupPetition",
 *      "petition" = "Civix\CoreBundle\Entity\Poll\Question\Petition",
 *      "payment_request" = "Civix\CoreBundle\Entity\Poll\Question\PaymentRequest",
 *      "representative_payment_request" = "Civix\CoreBundle\Entity\Poll\Question\RepresentativePaymentRequest",
 *      "group_payment_request" = "Civix\CoreBundle\Entity\Poll\Question\GroupPaymentRequest",
 *      "leader_event" = "Civix\CoreBundle\Entity\Poll\Question\LeaderEvent",
 *      "representative_event" = "Civix\CoreBundle\Entity\Poll\Question\RepresentativeEvent",
 *      "group_event" = "Civix\CoreBundle\Entity\Poll\Question\GroupEvent"
 * })
 * @Serializer\ExclusionPolicy("all")
 * 
 * @method setOwner(LeaderContentRootInterface $group)
 * @Assert\Callback(callback="areOptionsValid", groups={"publish"})
 * @PublishDate(objectName="Poll", groups={"update", "publish"})
 * @PublishedPollAmount(groups={"publish"})
 */
abstract class Question implements LeaderContentInterface, SubscriptionInterface, CommentedInterface, HashTaggableInterface, GroupSectionInterface
{
    use HashTaggableTrait, GroupSectionTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-answers-list", "api-poll-public", "api-leader-poll"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="text", nullable=true)
     * @Assert\NotBlank(groups={"Default", "update"})
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     */
    protected $subject;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Option",
     *      mappedBy="question",
     *      cascade={"persist"},
     *      orphanRemoval=true
     * )
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     */
    protected $options;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Answer",
     *      mappedBy="question",
     *      cascade={"remove", "persist"},
     *      orphanRemoval=true
     * )
     * @Serializer\Expose()
     * @Serializer\Groups({"activity-list"})
     */
    protected $answers;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "activity-list"})
     *
     * @ORM\OneToMany(
     *      targetEntity="EducationalContext", 
     *      mappedBy="question", 
     *      cascade={"remove", "persist"},
     *      orphanRemoval=true
     * )
     *
     * @Assert\Count(max="3", groups={"context"}, maxMessage="This poll should contain {{ limit }} educational contexts or less.")
     */
    protected $educationalContext;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expire_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    protected $expireAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="published_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     * @Serializer\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    protected $publishedAt;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll"})
     * @Serializer\Accessor(getter="isAnswered")
     * @Serializer\Type("boolean")
     * @Serializer\ReadOnly()
     */
    protected $isAnswered;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="question", cascade={"remove","persist"})
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"activity-list"})
     * @Serializer\Since("2")
     */
    protected $comments;

    /**
     * @ORM\Column(name="answers_count", type="integer", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities", "api-poll"})
     *
     * @var int
     */
    protected $answersCount;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(name="group_id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-leader-poll"})
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Representative")
     * @ORM\JoinColumn(name="representative_id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-poll-public", "api-leader-poll"})
     */
    protected $representative;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Superuser")
     * @ORM\JoinColumn(name="superuser_id", onDelete="CASCADE")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-leader-poll"})
     */
    protected $superuser;

    /**
     * @var ArrayCollection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Civix\CoreBundle\Entity\User", cascade={"persist"}, mappedBy="pollSubscriptions", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="poll_subscribers", joinColumns={@ORM\JoinColumn(name="question_id", referencedColumnName="id")})
     */
    private $subscribers;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "api-leader-poll"})
     */
    protected $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->educationalContext = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->hashTags = new ArrayCollection();
        $this->groupSections = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
    }

    abstract public function getType();

    /**
     * @return LeaderContentRootInterface|Group|Representative
     */
    abstract public function getOwner();

    /**
     * Return true if question answered
     * Can be used when question fetch by findAsUser() method.
     *
     * @return bool
     */
    public function isAnswered()
    {
        return (bool) $this->getAnswers()->count();
    }

    /**
     * Can be used when question fetch by findAsUser() method.
     *
     * @Serializer\Groups({"api-poll"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("answer_entity")
     * @Serializer\Until("1")
     */
    public function answerEntity()
    {
        return $this->isAnswered() ? $this->getAnswers()->first() : null;
    }

    /**
     * Return logged in user's answer
     *
     * @Serializer\Groups({"api-poll"})
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("answer")
     * @Serializer\Since("2")
     */
    public function getAnswer()
    {
        return $this->isAnswered() ? $this->getAnswers()->first() : null;
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
     * Set subject.
     *
     * @param string $subject
     *
     * @return Question
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
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
     * @return Question
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
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function setCurrentTimeAsUpdatedAt()
    {
        $this->setUpdatedAt(new \DateTime('now'));
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return Question
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
     * Set publishedAt.
     *
     * @param \DateTime $publishedAt
     *
     * @return Question
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Get publishedAt.
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     * @param \DateTime $expireAt
     *
     * @return $this
     */
    public function setExpireAt(\DateTime $expireAt)
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * Set answers_count.
     *
     * @param int $answersCount
     *
     * @return Question
     */
    public function setAnswersCount($answersCount)
    {
        $this->answersCount = $answersCount;

        return $this;
    }

    /**
     * Get answers_count.
     *
     * @return int
     */
    public function getAnswersCount()
    {
        return $this->answersCount;
    }

    /**
     * Add options.
     *
     * @param Option $options
     *
     * @return Question
     */
    public function addOption(Option $options)
    {
        $options->setQuestion($this);

        $this->options[] = $options;

        return $this;
    }

    /**
     * Remove options.
     *
     * @param Option $options
     */
    public function removeOption(Option $options)
    {
        $this->options->removeElement($options);
    }

    /**
     * Get options.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get educationalContext.
     *
     * @return ArrayCollection
     */
    public function getEducationalContext()
    {
        return $this->educationalContext;
    }

    /**
     * Add educationalText.
     *
     * @param EducationalContext $educationalContext
     * @return Question
     */
    public function addEducationalContext(EducationalContext $educationalContext)
    {
        $this->educationalContext[] = $educationalContext;
        $educationalContext->setQuestion($this);

        return $this;
    }

    /**
     * Remove educationalText.
     * @param EducationalContext $educationalContext
     */
    public function removeEducationalContext(EducationalContext $educationalContext)
    {
        $this->educationalContext->removeElement($educationalContext);
    }

    /**
     * hotfix: detach education context item for form update.
     */
    public function clearEducationalContext()
    {
        foreach ($this->educationalContext as $context) {
            $this->removeEducationalContext($context);
            $context->setQuestion(null);
        }
    }

    /**
     * Add answers.
     *
     * @param Answer $answers
     *
     * @return Question
     */
    public function addAnswer(Answer $answers)
    {
        $this->answers[] = $answers;
        $answers->setQuestion($this);

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
     * @return int
     */
    public function getMaxAnswers()
    {
        $max = 0;
        foreach ($this->getOptions() as $option) {
            if ($max < $option->getAnswers()->count()) {
                $max = $option->getAnswers()->count();
            }
        }

        return $max;
    }

    public function getStatistic($colors = array())
    {
        $sum = $this->getAnswers()->count();
        $max = $this->getMaxAnswers();

        $statistics = array();

        foreach ($this->getOptions() as $option) {
            if (!current($colors)) {
                reset($colors);
            }

            /* @var $option  \Civix\CoreBundle\Entity\Poll\Option */
            $stat = array(
                'option' => $option,
                'percent_answer' => $sum > 0 ? round($option->getAnswers()->count() / $sum * 100) : 0,
                'percent_width' => $max > 0 ? round($option->getAnswers()->count() / $max * 100) : 0,
                'color' => current($colors),
            );

            if (1 > $stat['percent_width']) {
                $stat['percent_width'] = 1;
            }

            $statistics[] = $stat;

            next($colors);
        }

        return $statistics;
    }

    /**
     * Add comment
     *
     * @param BaseComment|Comment $comment
     * @return $this
     */
    public function addComment(BaseComment $comment)
    {
        $this->comments[] = $comment;
        $comment->setQuestion($this);

        return $this;
    }

    /**
     * Remove comment
     *
     * @param BaseComment $comment
     */
    public function removeComment(BaseComment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return Collection|Comment[]
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @Serializer\Groups({"api-poll"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("share_picture")
     * @Serializer\Type("Civix\CoreBundle\Serializer\Type\Image")
     */
    public function getSharePicture()
    {
        $entity = $this->getOwner();

        return new Image($entity, 'avatar');
    }

    /**
     * @return Group|LeaderContentRootInterface
     */
    public function getGroup()
    {
        return $this->getOwner();
    }

    /**
     * @return Representative|LeaderContentRootInterface
     */
    public function getRepresentative()
    {
        return $this->getOwner();
    }
    /**
     * Add subscriber
     *
     * @param User $subscriber
     * @return Question
     */
    public function addSubscriber(User $subscriber)
    {
        $this->subscribers[] = $subscriber;

        return $this;
    }

    /**
     * Remove subscriber
     *
     * @param User $subscriber
     */
    public function removeSubscriber(User $subscriber)
    {
        $this->subscribers->removeElement($subscriber);
    }

    /**
     * Get subscribers
     *
     * @return ArrayCollection|User[]
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }

    /**
     * @return bool
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"activity-list"})
     */
    public function isSubscribed()
    {
        return (bool)$this->subscribers->count();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Question
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function areOptionsValid(ExecutionContext $context)
    {
        if (!$this instanceof LeaderNews && $this->getOptions()->count() < 2) {
            $context->addViolation('You must specify at least two options');
        }
    }

    public function getRoot()
    {
        return $this->getGroup();
    }

    public function setRoot(LeaderContentRootInterface $root)
    {
        return $this->group = $root;
    }
}

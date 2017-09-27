<?php

namespace Civix\CoreBundle\Entity\Post;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Table(name="post_votes")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Post\VoteRepository")
 * @Serializer\ExclusionPolicy("all")
 * @Assert\Callback(callback="isPostExpired")
 * @Assert\Callback(callback="isPostAuthor")
 */
class Vote
{
    const OPTION_UPVOTE = 1;
    const OPTION_DOWNVOTE = 2;
    const OPTION_IGNORE = 3;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"post-votes", "api-answers-list", "api-leader-answers", "api-petitions-answers", "api-activities"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-leader-answers"})
     */
    protected $user;

    /**
     * @ORM\Column(name="`option`", type="integer")
     * @Serializer\Expose()
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getOptions", message="Incorrect vote's option", strict=true)
     * @Serializer\Groups({"post-votes", "api-leader-answers", "api-answers-list", "api-petitions-answers", "api-activities"})
     */
    protected $option;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-leader-answers", "api-petitions-answers", "api-activities"})
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Post", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $post;

    public static function getOptionTitles()
    {
        return [
            self::OPTION_UPVOTE => 'upvote',
            self::OPTION_DOWNVOTE => 'downvote',
            self::OPTION_IGNORE => 'ignore',
        ];
    }

    public static function getOptions()
    {
        return [
            self::OPTION_UPVOTE,
            self::OPTION_DOWNVOTE,
            self::OPTION_IGNORE,
        ];
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
     * Set option.
     *
     * @param int $option
     *
     * @return Vote
     */
    public function setOption($option)
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Get option.
     *
     * @return int
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Return option's title
     *
     * @return string
     */
    public function getOptionTitle(): string
    {
        return self::getOptionTitles()[$this->getOption()] ?? '';
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return Vote
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
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set post.
     *
     * @param Post $post
     *
     * @return Vote
     */
    public function setPost(Post $post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post.
     *
     * @return Post
     */
    public function getPost()
    {
        return $this->post;
    }

    public function isPostExpired(ExecutionContextInterface $context)
    {
        $post = $this->post;
        if ($post instanceof Post && $post->getExpiredAt() < new \DateTime()) {
            $context->addViolation('You could not answer to expired post.');
        }
    }

    public function isPostAuthor(ExecutionContextInterface $context)
    {
        $post = $this->post;
        if ($post instanceof Post && $post->getUser() == $this->getUser()) {
            $context->addViolation('You could not answer to your post.');
        }
    }

    public function isUpvote(): bool
    {
        return $this->option === self::OPTION_UPVOTE;
    }
}

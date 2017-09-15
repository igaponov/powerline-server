<?php

namespace Civix\CoreBundle\Entity\Poll;

use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\BaseComment;

/**
 * Comments entity.
 *
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Poll\CommentRepository")
 * @ORM\Table(name="poll_comments")
 * @Serializer\ExclusionPolicy("all")
 */
class Comment extends BaseComment
{
    /**
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false, name="question_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $question;

    public function __construct(User $user, Comment $parentComment = null)
    {
        parent::__construct($user, $parentComment);
    }

    /**
     * Set question.
     *
     * @param Question $question
     * 
     * @return Comment
     */
    public function setQuestion(Question $question): Comment
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return Question
     */
    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function getCommentedEntity(): CommentedInterface
    {
        return $this->getQuestion();
    }

    public function getEntityType(): string
    {
        return 'poll';
    }
}

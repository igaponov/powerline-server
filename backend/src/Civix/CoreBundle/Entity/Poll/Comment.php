<?php

namespace Civix\CoreBundle\Entity\Poll;

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
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="childrenComments")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentComment;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="parentComment")
     */
    protected $childrenComments;

    /**
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false, name="question_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $question;

    /**
     * Set question.
     *
     * @param Question $question
     * 
     * @return Comment
     */
    public function setQuestion(Question $question)
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
}

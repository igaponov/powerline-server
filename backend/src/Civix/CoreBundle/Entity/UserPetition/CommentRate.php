<?php

namespace Civix\CoreBundle\Entity\UserPetition;

use Civix\CoreBundle\Entity\BaseCommentRate;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\CommentRateRepository")
 * @ORM\Table(name="user_petition_comment_rates")
 */
class CommentRate extends BaseCommentRate
{
    /**
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="rates")
     * @ORM\JoinColumn(name="comment_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $comment;

    /**
     * Set comment.
     *
     * @param Comment $comment
     * 
     * @return CommentRate
     */
    public function setComment(Comment $comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return Comment
     */
    public function getComment()
    {
        return $this->comment;
    }
}

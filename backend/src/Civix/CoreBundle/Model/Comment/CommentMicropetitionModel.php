<?php

namespace Civix\CoreBundle\Model\Comment;

class CommentMicropetitionModel implements CommentModelInterface
{
    public function getRepositoryName()
    {
        return 'Civix\CoreBundle\Entity\Micropetitions\Comment';
    }

    public function setEntityForComment($entity, $comment)
    {
        $comment->setPetition($entity);

        return $comment;
    }

    public function getEntityForComment($comment)
    {
        return $comment->getPetition();
    }
}

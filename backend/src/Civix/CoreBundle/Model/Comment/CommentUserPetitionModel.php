<?php

namespace Civix\CoreBundle\Model\Comment;

class CommentUserPetitionModel implements CommentModelInterface
{
    public function getRepositoryName()
    {
        return 'Civix\CoreBundle\Entity\UserPetition\Comment';
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

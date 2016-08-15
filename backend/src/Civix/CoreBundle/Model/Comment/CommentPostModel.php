<?php

namespace Civix\CoreBundle\Model\Comment;

class CommentPostModel implements CommentModelInterface
{
    public function getRepositoryName()
    {
        return 'Civix\CoreBundle\Entity\Post\Comment';
    }

    public function setEntityForComment($entity, $comment)
    {
        $comment->setPost($entity);

        return $comment;
    }

    public function getEntityForComment($comment)
    {
        return $comment->getPost();
    }
}

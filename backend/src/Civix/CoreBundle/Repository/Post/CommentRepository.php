<?php

namespace Civix\CoreBundle\Repository\Post;

use Civix\CoreBundle\Repository\CommentRepository as BaseCommentRepository;

class CommentRepository extends BaseCommentRepository
{
    public function getCommentEntityField()
    {
        return 'post';
    }
}

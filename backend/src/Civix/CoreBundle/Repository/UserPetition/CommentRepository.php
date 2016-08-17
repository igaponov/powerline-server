<?php

namespace Civix\CoreBundle\Repository\UserPetition;

use Civix\CoreBundle\Repository\CommentRepository as BaseCommentRepository;

class CommentRepository extends BaseCommentRepository
{
    public function getCommentEntityField()
    {
        return 'petition';
    }
}

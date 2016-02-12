<?php

namespace Civix\CoreBundle\Repository\Micropetitions;

use Civix\CoreBundle\Repository\CommentRepository as BaseCommentRepository;
use Civix\CoreBundle\Entity\Micropetitions\Petition;

class CommentRepository extends BaseCommentRepository
{
    public function getCommentEntityField()
    {
        return 'petition';
    }
}

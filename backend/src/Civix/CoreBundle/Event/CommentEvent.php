<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\BaseComment;
use Symfony\Component\EventDispatcher\Event;

class CommentEvent extends Event
{
    /**
     * @var BaseComment
     */
    private $comment;

    public function __construct(BaseComment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return BaseComment
     */
    public function getComment()
    {
        return $this->comment;
    }
}
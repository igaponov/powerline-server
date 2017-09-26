<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class PostShareEvent extends Event
{
    /**
     * @var Post
     */
    private $post;
    /**
     * @var User
     */
    private $sharer;

    public function __construct(Post $post, User $sharer)
    {
        $this->post = $post;
        $this->sharer = $sharer;
    }

    /**
     * @return Post
     */
    public function getPost(): Post
    {
        return $this->post;
    }

    /**
     * @return User
     */
    public function getSharer(): User
    {
        return $this->sharer;
    }
}
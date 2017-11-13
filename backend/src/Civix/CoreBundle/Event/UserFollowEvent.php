<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class UserFollowEvent extends Event
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var User
     */
    private $follower;

    public function __construct(User $user, User $follower)
    {
        $this->user = $user;
        $this->follower = $follower;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return User
     */
    public function getFollower(): User
    {
        return $this->follower;
    }
}
<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class UserRepresentativeEvent extends Event
{
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Symfony\Component\EventDispatcher\Event;

class UserPetitionShareEvent extends Event
{
    /**
     * @var UserPetition
     */
    private $petition;
    /**
     * @var User
     */
    private $sharer;

    public function __construct(UserPetition $petition, User $sharer)
    {
        $this->petition = $petition;
        $this->sharer = $sharer;
    }

    /**
     * @return UserPetition
     */
    public function getPetition(): UserPetition
    {
        return $this->petition;
    }

    /**
     * @return User
     */
    public function getSharer(): User
    {
        return $this->sharer;
    }
}
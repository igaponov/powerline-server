<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\UserPetition;
use Symfony\Component\EventDispatcher\Event;

class UserPetitionEvent extends Event
{
    /**
     * @var UserPetition
     */
    private $petition;

    public function __construct(UserPetition $petition)
    {
        $this->petition = $petition;
    }

    /**
     * @return UserPetition
     */
    public function getPetition()
    {
        return $this->petition;
    }
}
<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\UserRepresentative;
use Symfony\Component\EventDispatcher\Event;

class UserRepresentativeEvent extends Event
{
    /**
     * @var UserRepresentative
     */
    private $representative;

    public function __construct(UserRepresentative $representative)
    {
        $this->representative = $representative;
    }

    /**
     * @return UserRepresentative
     */
    public function getRepresentative(): UserRepresentative
    {
        return $this->representative;
    }
}
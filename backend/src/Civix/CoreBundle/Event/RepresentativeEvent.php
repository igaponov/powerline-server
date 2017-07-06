<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Representative;
use Symfony\Component\EventDispatcher\Event;

class RepresentativeEvent extends Event
{
    /**
     * @var Representative
     */
    private $representative;

    public function __construct(Representative $representative)
    {
        $this->representative = $representative;
    }

    /**
     * @return Representative
     */
    public function getRepresentative(): Representative
    {
        return $this->representative;
    }
}
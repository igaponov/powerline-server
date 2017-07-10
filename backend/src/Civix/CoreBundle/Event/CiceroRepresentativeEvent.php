<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\CiceroRepresentative;
use Symfony\Component\EventDispatcher\Event;

class CiceroRepresentativeEvent extends Event
{
    /**
     * @var CiceroRepresentative
     */
    private $representative;

    public function __construct(CiceroRepresentative $representative)
    {
        $this->representative = $representative;
    }

    /**
     * @return CiceroRepresentative
     */
    public function getRepresentative(): CiceroRepresentative
    {
        return $this->representative;
    }
}
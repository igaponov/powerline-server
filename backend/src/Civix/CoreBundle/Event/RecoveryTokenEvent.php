<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\RecoveryToken;
use Symfony\Component\EventDispatcher\Event;

class RecoveryTokenEvent extends Event
{
    /**
     * @var RecoveryToken
     */
    private $recoveryToken;

    public function __construct(RecoveryToken $recoveryToken)
    {
        $this->recoveryToken = $recoveryToken;
    }

    /**
     * @return RecoveryToken
     */
    public function getRecoveryToken(): RecoveryToken
    {
        return $this->recoveryToken;
    }
}
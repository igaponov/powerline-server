<?php
namespace Civix\CoreBundle\Event\UserPetition;

use Civix\CoreBundle\Entity\UserPetition\Signature;
use Symfony\Component\EventDispatcher\Event;

class SignatureEvent extends Event
{
    /**
     * @var Signature
     */
    private $signature;

    public function __construct(Signature $signature)
    {
        $this->signature = $signature;
    }

    /**
     * @return Signature
     */
    public function getSignature()
    {
        return $this->signature;
    }
}
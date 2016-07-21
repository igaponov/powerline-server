<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Invites\UserToGroup;
use Symfony\Component\EventDispatcher\Event;

class InviteEvent extends Event
{
    /**
     * @var UserToGroup
     */
    private $invite;

    public function __construct(UserToGroup $invite)
    {
        $this->invite = $invite;
    }

    /**
     * @return UserToGroup
     */
    public function getInvite()
    {
        return $this->invite;
    }
}
<?php

namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class UserAnnouncementsEvent extends Event
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var Announcement[]
     */
    private $announcements;

    public function __construct(User $user, Announcement ...$announcements)
    {
        $this->user = $user;
        $this->announcements = $announcements;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Announcement[]
     */
    public function getAnnouncements(): array
    {
        return $this->announcements;
    }
}
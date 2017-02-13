<?php

namespace Civix\ApiBundle\Entity;

use Civix\CoreBundle\Entity\Announcement;

class AnnouncementCollection
{
    /**
     * @var Announcement[]
     */
    private $announcements;
    /**
     * @var boolean
     */
    private $read;

    /**
     * @param Announcement[] ...$announcements
     */
    public function __construct(Announcement ...$announcements)
    {
        $this->announcements = $announcements;
    }

    /**
     * @return Announcement[]
     */
    public function getAnnouncements()
    {
        return $this->announcements;
    }

    /**
     * @param bool $read
     * @return AnnouncementCollection
     */
    public function setRead(bool $read): AnnouncementCollection
    {
        $this->read = $read;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getRead(): bool
    {
        return $this->read;
    }
}
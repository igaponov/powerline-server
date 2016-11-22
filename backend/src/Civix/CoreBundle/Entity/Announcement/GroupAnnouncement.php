<?php

namespace Civix\CoreBundle\Entity\Announcement;

use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Announcement;

/**
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class GroupAnnouncement extends Announcement implements GroupSectionInterface
{
    /**
     * Set group.
     *
     * @param \Civix\CoreBundle\Entity\Group $group
     *
     * @return GroupAnnouncement
     */
    public function setRoot(\Civix\CoreBundle\Entity\Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return \Civix\CoreBundle\Entity\Group
     */
    public function getRoot()
    {
        return $this->group;
    }
}

<?php

namespace Civix\CoreBundle\Entity\Announcement;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class GroupAnnouncement extends Announcement implements GroupSectionInterface
{
    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @param Group|LeaderContentRootInterface $group
     * @return GroupAnnouncement
     */
    public function setGroup(Group $group): GroupAnnouncement
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Set group.
     *
     * @param LeaderContentRootInterface $root
     * @return GroupAnnouncement
     * @internal param Group $group
     */
    public function setRoot(LeaderContentRootInterface $root): GroupAnnouncement
    {
        return $this->setGroup($root);
    }

    /**
     * Get group.
     *
     * @return LeaderContentRootInterface|Group
     */
    public function getRoot(): LeaderContentRootInterface
    {
        return $this->getGroup();
    }
}

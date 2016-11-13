<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Group event entity.
 *
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Poll\LeaderEventRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class GroupEvent extends LeaderEvent
{
    public function getType()
    {
        return 'group_event';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\Group $group
     *
     * @return GroupEvent
     */
    public function setOwner(\Civix\CoreBundle\Entity\Group $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\Group
     */
    public function getOwner()
    {
        return $this->group;
    }
}

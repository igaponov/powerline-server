<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Civix\CoreBundle\Model\Group\GroupSectionTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Model\Group\GroupSectionInterface;

/**
 * Representative news entity.
 *
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class GroupNews extends LeaderNews  implements GroupSectionInterface
{
    use GroupSectionTrait;

    public function getType()
    {
        return 'group_news';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\Group $group
     *
     * @return $this
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

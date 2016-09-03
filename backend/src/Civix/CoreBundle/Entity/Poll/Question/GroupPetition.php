<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Civix\CoreBundle\Model\Group\GroupSectionTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Model\Group\GroupSectionInterface;

/**
 * Group petition entity.
 *
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class GroupPetition extends Petition implements GroupSectionInterface
{
    use GroupSectionTrait;

    public function getType()
    {
        return 'group_petition';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\Group $group
     *
     * @return GroupPetition
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

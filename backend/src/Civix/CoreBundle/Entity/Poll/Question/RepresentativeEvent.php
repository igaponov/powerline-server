<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Representative event entity.
 *
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Poll\LeaderEventRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class RepresentativeEvent extends LeaderEvent
{
    public function getType()
    {
        return 'representative_event';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\UserRepresentative $representative
     *
     * @return RepresentativeEvent
     */
    public function setOwner(\Civix\CoreBundle\Entity\UserRepresentative $representative)
    {
        $this->representative = $representative;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\UserRepresentative
     */
    public function getOwner()
    {
        return $this->representative;
    }
}

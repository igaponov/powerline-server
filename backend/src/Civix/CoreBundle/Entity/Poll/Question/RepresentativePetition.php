<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Representative petition entity.
 *
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class RepresentativePetition extends Petition
{
    public function getType()
    {
        return 'representative_petition';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\UserRepresentative $representative
     *
     * @return RepresentativePetition
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

<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Doctrine\ORM\Mapping as ORM;
use Civix\CoreBundle\Entity\Poll\Question;
use JMS\Serializer\Annotation as Serializer;

/**
 * Representative question entity.
 *
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class Representative extends Question
{
    public function getType()
    {
        return 'representative_question';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\UserRepresentative $representative
     *
     * @return Representative
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

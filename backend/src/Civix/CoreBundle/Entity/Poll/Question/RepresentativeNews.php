<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Representative news entity.
 *
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class RepresentativeNews extends LeaderNews
{
    public function getType()
    {
        return 'representative_news';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\Representative $representative
     *
     * @return RepresentativeNews
     */
    public function setOwner(\Civix\CoreBundle\Entity\Representative $representative)
    {
        $this->representative = $representative;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function getOwner()
    {
        return $this->representative;
    }
}

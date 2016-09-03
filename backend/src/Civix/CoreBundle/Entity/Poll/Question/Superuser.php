<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Doctrine\ORM\Mapping as ORM;
use Civix\CoreBundle\Entity\Poll\Question;
use JMS\Serializer\Annotation as Serializer;

/**
 * Superuser question entity.
 *
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class Superuser extends Question
{
    public function getType()
    {
        return 'superuser_question';
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\Superuser $superuser
     *
     * @return Superuser
     */
    public function setOwner(\Civix\CoreBundle\Entity\Superuser $superuser)
    {
        $this->superuser = $superuser;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\Superuser
     */
    public function getOwner()
    {
        return $this->superuser;
    }
}

<?php

namespace Civix\CoreBundle\Entity\Activities;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Activity;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 * @Vich\Uploadable
 */
class LeaderNews extends Activity
{
    public function getEntity()
    {
        return array(
            'type' => self::TYPE_LEADER_NEWS,
            'id' => $this->getQuestion() ? $this->getQuestion()->getId() : null,
        );
    }
}

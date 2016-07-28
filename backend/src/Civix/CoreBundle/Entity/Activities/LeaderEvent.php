<?php

namespace Civix\CoreBundle\Entity\Activities;

use Civix\CoreBundle\Entity\Activity;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 * @Vich\Uploadable
 */
class LeaderEvent extends Question
{
    public function getEntity()
    {
        return [
            'type' => Activity::TYPE_LEADER_EVENT,
            'id' => $this->getQuestionId(),
        ];
    }
}

<?php

namespace Civix\CoreBundle\Entity\Activities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Activity;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 * @Vich\Uploadable
 */
class Question extends Activity
{
    public function getEntity()
    {
        return array(
            'type' => self::TYPE_QUESTION,
            'id' => $this->getQuestion() ? $this->getQuestion()->getId() : null,
        );
    }

    /**
     * @return Collection|\Civix\CoreBundle\Entity\UserPetition\Signature[]
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("ArrayCollection")
     * @Serializer\Groups({"api-activities"})
     */
    public function getAnswers()
    {
        if ($this->getQuestion()) {
            return $this->getQuestion()->getAnswers();
        }

        return new ArrayCollection();
    }
}

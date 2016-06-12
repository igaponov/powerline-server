<?php

namespace Civix\CoreBundle\Entity\Activities;

use Civix\CoreBundle\Entity\Micropetitions;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Activity;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 */
class MicroPetition extends Activity
{
    /**
     * @var int
     * @ORM\Column(name="quorum", type="integer")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities"})
     */
    protected $quorum;

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-activities"})
     * @return null|Micropetitions\Metadata
     */
    public function getMetadata()
    {
        if ($this->petition) {
            return $this->petition->getMetadata();
        }

        return null;
    }

    public function getEntity()
    {
        return array(
            'type' => 'micro-petition',
            'id' => $this->getPetition() ? $this->getPetition()->getId() : null,
            'group_id' => $this->getGroup() ? $this->getGroup()->getId() : null,
        );
    }

    /**
     * @param int $quorum
     */
    public function setQuorum($quorum)
    {
        $this->quorum = $quorum;
    }

    /**
     * @return int
     */
    public function getQuorum()
    {
        return $this->quorum;
    }
}

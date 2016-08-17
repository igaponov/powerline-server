<?php

namespace Civix\CoreBundle\Entity\Activities;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Micropetitions;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 */
class UserPetition extends Activity
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
            'type' => self::TYPE_USER_PETITION,
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

    /**
     * @return int|void
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("integer")
     * @Serializer\Groups({"api-activities"})
     */
    public function getCommentsCount()
    {
        if ($this->petition) {
            return $this->petition->getComments()->count();
        }

        return 0;
    }

    /**
     * @return ArrayCollection|\Civix\CoreBundle\Entity\UserPetition\Signature[]
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Type("ArrayCollection")
     * @Serializer\Groups({"api-activities"})
     */
    public function getAnswers()
    {
        if ($this->petition) {
            return $this->petition->getSignatures();
        }

        return new ArrayCollection();
    }
}

<?php

namespace Civix\CoreBundle\Entity\Announcement;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\Representative;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\Announcement;

/**
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class RepresentativeAnnouncement extends Announcement
{
    /**
     * @return Representative
     */
    public function getRepresentative()
    {
        return $this->representative;
    }

    /**
     * @param Representative|LeaderContentRootInterface $representative
     * @return RepresentativeAnnouncement
     */
    public function setRepresentative(Representative $representative)
    {
        $this->representative = $representative;

        return $this;
    }

    /**
     * Set representative.
     *
     * @param LeaderContentRootInterface $root
     * @return Announcement
     * @internal param \Civix\CoreBundle\Entity\Representative $representative
     *
     */
    public function setRoot(LeaderContentRootInterface $root)
    {
        return $this->setRepresentative($root);
    }

    /**
     * Get representative.
     *
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function getRoot()
    {
        return $this->getRepresentative();
    }
}

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
    public function getRepresentative(): Representative
    {
        return $this->representative;
    }

    /**
     * @param Representative|LeaderContentRootInterface $representative
     * @return RepresentativeAnnouncement
     */
    public function setRepresentative(Representative $representative): RepresentativeAnnouncement
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
    public function setRoot(LeaderContentRootInterface $root): Announcement
    {
        return $this->setRepresentative($root);
    }

    /**
     * Get representative.
     *
     * @return LeaderContentRootInterface|Representative
     */
    public function getRoot(): LeaderContentRootInterface
    {
        return $this->getRepresentative();
    }
}

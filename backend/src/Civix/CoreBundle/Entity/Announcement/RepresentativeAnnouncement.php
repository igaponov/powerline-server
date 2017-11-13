<?php

namespace Civix\CoreBundle\Entity\Announcement;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\UserRepresentative;
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
     * @return UserRepresentative
     */
    public function getRepresentative(): UserRepresentative
    {
        return $this->representative;
    }

    /**
     * @param UserRepresentative|LeaderContentRootInterface $representative
     * @return RepresentativeAnnouncement
     */
    public function setRepresentative(UserRepresentative $representative): RepresentativeAnnouncement
    {
        $this->representative = $representative;

        return $this;
    }

    /**
     * Set representative.
     *
     * @param LeaderContentRootInterface $root
     * @return Announcement
     * @internal param \Civix\CoreBundle\Entity\UserRepresentative $representative
     *
     */
    public function setRoot(LeaderContentRootInterface $root): Announcement
    {
        return $this->setRepresentative($root);
    }

    /**
     * Get representative.
     *
     * @return LeaderContentRootInterface|\Civix\CoreBundle\Entity\UserRepresentative
     */
    public function getRoot(): LeaderContentRootInterface
    {
        return $this->getRepresentative();
    }
}

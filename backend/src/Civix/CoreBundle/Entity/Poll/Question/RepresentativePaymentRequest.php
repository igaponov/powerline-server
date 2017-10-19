<?php

namespace Civix\CoreBundle\Entity\Poll\Question;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Representative petition entity.
 *
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Poll\PaymentRequestRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class RepresentativePaymentRequest extends PaymentRequest
{
    public function getType()
    {
        return 'representative_'.parent::getType();
    }

    /**
     * Set user.
     *
     * @param \Civix\CoreBundle\Entity\UserRepresentative $representative
     *
     * @return RepresentativePaymentRequest
     */
    public function setOwner(\Civix\CoreBundle\Entity\UserRepresentative $representative)
    {
        $this->representative = $representative;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Civix\CoreBundle\Entity\UserRepresentative
     */
    public function getOwner()
    {
        return $this->representative;
    }
}

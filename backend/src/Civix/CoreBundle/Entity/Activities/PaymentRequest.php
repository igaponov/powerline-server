<?php

namespace Civix\CoreBundle\Entity\Activities;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @Serializer\ExclusionPolicy("all")
 * @Vich\Uploadable
 */
class PaymentRequest extends Question
{
    public function getEntity()
    {
        return array(
            'type' => self::TYPE_PAYMENT_REQUEST,
            'id' => $this->getQuestion() ? $this->getQuestion()->getId() : null,
        );
    }
}

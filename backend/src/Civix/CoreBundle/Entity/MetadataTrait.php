<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait MetadataTrait
{
    /**
     * @var Metadata
     *
     * @ORM\Column(type="object")
     * @Serializer\Expose()
     */
    protected $metadata;

    /**
     * @param Metadata $metadata
     * @return $this
     */
    public function setMetadata(Metadata $metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
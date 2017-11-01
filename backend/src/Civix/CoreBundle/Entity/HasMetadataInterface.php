<?php

namespace Civix\CoreBundle\Entity;

interface HasMetadataInterface
{
    /**
     * @param Metadata $metadata
     * @return $this
     */
    public function setMetadata(Metadata $metadata);

    /**
     * @return Metadata
     */
    public function getMetadata(): ?Metadata;
}
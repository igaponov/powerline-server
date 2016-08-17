<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait HashTaggableTrait
{
    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="Civix\CoreBundle\Entity\HashTag")
     */
    protected $hashTags;

    /**
     * @var array
     *
     * @ORM\Column(name="cached_hash_tags", type="array", nullable=true)
     * @Serializer\Expose()
     */
    protected $cachedHashTags = [];

    /**
     * Add hashTags.
     *
     * @param HashTag $hashTags
     *
     * @return $this
     */
    public function addHashTag(HashTag $hashTags)
    {
        $this->hashTags[] = $hashTags;

        return $this;
    }

    /**
     * Remove hashTags.
     *
     * @param HashTag $hashTags
     */
    public function removeHashTag(HashTag $hashTags)
    {
        $this->hashTags->removeElement($hashTags);
    }

    /**
     * Get hashTags.
     *
     * @return Collection
     */
    public function getHashTags()
    {
        return $this->hashTags;
    }
}
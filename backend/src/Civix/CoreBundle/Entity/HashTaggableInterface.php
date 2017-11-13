<?php
namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface HashTaggableInterface
{
    /**
     * Add hashTags.
     *
     * @param HashTag $hashTags
     *
     * @return $this
     */
    public function addHashTag(HashTag $hashTags);

    /**
     * Remove hashTags.
     *
     * @param HashTag $hashTags
     */
    public function removeHashTag(HashTag $hashTags): void;

    /**
     * Get hashTags.
     *
     * @return Collection|HashTag[]
     */
    public function getHashTags(): Collection;

    /**
     * Set cachedHashTags.
     *
     * @param array $cachedHashTags
     *
     * @return $this
     */
    public function setCachedHashTags(array $cachedHashTags);

    /**
     * Get cachedHashTags.
     *
     * @return array
     */
    public function getCachedHashTags(): array;
}
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
    public function removeHashTag(HashTag $hashTags);

    /**
     * Get hashTags.
     *
     * @return Collection|HashTag[]
     */
    public function getHashTags();

    /**
     * Set cachedHashTags.
     *
     * @param array $cachedHashTags
     *
     * @return $this
     */
    public function setCachedHashTags($cachedHashTags);

    /**
     * Get cachedHashTags.
     *
     * @return array
     */
    public function getCachedHashTags();
}
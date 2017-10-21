<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as Serializer;

/**
 * Trait UserSerializableTrait
 * @package Civix\CoreBundle\Entity
 * @property Collection|PersistentCollection $followers
 */
trait UserSerializableTrait
{
    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"activity-list"})
     * @Serializer\Type("string")
     *
     * @return string
     */
    public function getFollowStatus(): ?string
    {
        /** @var UserFollow $userFollow */
        if ((!$this->followers instanceof PersistentCollection || $this->followers->isInitialized())
            && $userFollow = $this->followers->first()
        ) {
            return $userFollow->getStatusLabel();
        }

        return null;
    }
}
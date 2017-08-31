<?php

namespace Civix\CoreBundle\Entity;

use JMS\Serializer\Annotation as Serializer;

trait GroupSerializableTrait
{
    /**
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"group-list"})
     */
    private $priorityItemCount = 0;

    /**
     * @return int
     */
    public function getPriorityItemCount(): int
    {
        return $this->priorityItemCount;
    }

    /**
     * @param int $priorityItemCount
     * @return $this
     */
    public function setPriorityItemCount(int $priorityItemCount): self
    {
        $this->priorityItemCount = $priorityItemCount;

        return $this;
    }
}
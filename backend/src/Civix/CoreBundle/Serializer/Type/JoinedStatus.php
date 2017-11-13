<?php

namespace Civix\CoreBundle\Serializer\Type;

use Civix\CoreBundle\Entity\Group;

class JoinedStatus
{
    /**
     * @return Group
     */
    protected $entity;

    public function __construct(Group $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): Group
    {
        return $this->entity;
    }
}

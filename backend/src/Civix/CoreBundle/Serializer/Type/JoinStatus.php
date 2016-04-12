<?php

namespace Civix\CoreBundle\Serializer\Type;

use Civix\CoreBundle\Entity\Group;

class JoinStatus
{
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return Group
     */
    public function getEntity()
    {
        return $this->entity;
    }
}

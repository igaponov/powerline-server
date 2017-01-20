<?php

namespace Civix\CoreBundle\Serializer\Type;

use Civix\CoreBundle\Entity\Group;

class ContentRemaining
{
    /**
     * @var string
     */
    private $contentType;
    /**
     * @var Group
     */
    private $group;

    public function __construct($contentType, Group $group)
    {
        $this->contentType = $contentType;
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }
}
<?php
namespace Civix\CoreBundle\Serializer\Type;

use Civix\CoreBundle\Entity\Group;

class TotalMembers
{
    /**
     * @var Group
     */
    private $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }
}
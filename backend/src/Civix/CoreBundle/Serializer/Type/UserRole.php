<?php
namespace Civix\CoreBundle\Serializer\Type;

use Civix\CoreBundle\Entity\UserGroup;

class UserRole
{
    /**
     * @var UserGroup
     */
    private $userGroup;

    public function __construct(UserGroup $userGroup)
    {
        $this->userGroup = $userGroup;
    }

    /**
     * @return UserGroup
     */
    public function getUserGroup()
    {
        return $this->userGroup;
    }
}
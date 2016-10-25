<?php
namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Serializer\Type\UserRole;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;

class UserRoleHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'UserRole',
                'method' => 'serialize',
            ),
        );
    }

    public function serialize(JsonSerializationVisitor $visitor, UserRole $userRole, array $type)
    {
        $userGroup = $userRole->getUserGroup();
        $group = $userGroup->getGroup();
        $user = $userGroup->getUser();
        if ($group->getOwner() && $group->getOwner()->isEqualTo($user)) {
            return 'owner';
        } elseif ($group->getManagers()->contains($user)) {
            return 'manager';
        } elseif ($userGroup->getUser()->isEqualTo($user)) {
            return 'member';
        }

        return '';
    }
}
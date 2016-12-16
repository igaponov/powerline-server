<?php
namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Serializer\Type\UserRole;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;

class UserRoleHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'UserRole',
                'method' => 'serialize',
            ],
        ];
    }

    public function serialize(JsonSerializationVisitor $visitor, UserRole $userRole, array $type, Context $context)
    {
        $userGroup = $userRole->getUserGroup();
        $group = $userGroup->getGroup();
        $user = $userGroup->getUser();
        if ($group->getOwner() && $group->getOwner()->isEqualTo($user)) {
            $result = 'owner';
        } elseif ($group->getManagers()->contains($user)) {
            $result = 'manager';
        } elseif ($userGroup->getUser()->isEqualTo($user)) {
            $result = 'member';
        } else {
            $result = '';
        }

        return $visitor->visitString($result, $type, $context);
    }
}
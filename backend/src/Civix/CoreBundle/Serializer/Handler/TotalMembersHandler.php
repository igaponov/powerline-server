<?php
namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Repository\UserGroupRepository;
use Civix\CoreBundle\Serializer\Type\TotalMembers;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;

class TotalMembersHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'TotalMembers',
                'method' => 'serialize',
            ],
        ];
    }

    public function serialize(JsonSerializationVisitor $visitor, TotalMembers $totalMembers, array $type, Context $context)
    {
        $group = $totalMembers->getGroup();
        $totalMemberCount = $group->getUserGroups()->count();

        return $visitor->visitInteger($totalMemberCount, $type, $context);
    }
}
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
    /**
     * @var UserGroupRepository
     */
    private $repository;

    public function __construct(UserGroupRepository $repository)
    {
        $this->repository = $repository;
    }

    public static function getSubscribingMethods()
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
        $totalMembers = $this->repository->getTotalMembers($group);

        return $visitor->visitInteger($totalMembers, $type, $context);
    }
}
<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Serializer\Type\JoinStatus;
use Doctrine\Common\Collections\AbstractLazyCollection;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class JoinStatusHandler implements SubscribingHandlerInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'JoinStatus',
                'method' => 'serialize',
            ],
        ];
    }

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function serialize(JsonSerializationVisitor $visitor, JoinStatus $joinStatus, array $type, Context $context)
    {
        $token = $this->tokenStorage->getToken();
        $collection = $joinStatus->getEntity()->getUserGroups();
        if (!$token
            || !($user = $token->getUser())
            || !$user instanceof User
            || ($collection instanceof AbstractLazyCollection && !$collection->isInitialized())
        ) {
            return $visitor->visitNull(null, $type, $context);
        }
        $filter = function (UserGroup $userGroup) use ($user) {
            return $userGroup->getUser()->isEqualTo($user);
        };
        /** @var UserGroup $userGroup */
        if ($userGroup = $collection->filter($filter)->first()) {
            return $visitor->visitString($userGroup->getJoinStatus(), $type, $context);
        }

        return $visitor->visitNull(null, $type, $context);
    }
}

<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\UserInterface;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Civix\CoreBundle\Serializer\Type\JoinedStatus;

class JoinedStatusHandler implements SubscribingHandlerInterface
{
    private $user;

    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'JoinedStatus',
                'method' => 'serialize',
            ],
        ];
    }

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->user = $tokenStorage->getToken()->getUser();
    }

    public function serialize(JsonSerializationVisitor $visitor, JoinedStatus $joinStatusType, array $type, Context $context)
    {
        $result = false;
        if ($this->user instanceof UserInterface) {
            $result = $visitor->visitBoolean(
                $joinStatusType->getEntity()->getJoined($this->user),
                $type,
                $context
            );
        }

        return $visitor->visitBoolean($result, $type, $context);
    }
}

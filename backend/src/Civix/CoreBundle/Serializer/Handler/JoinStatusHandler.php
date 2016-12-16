<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\UserInterface;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Civix\CoreBundle\Serializer\Type\JoinStatus;

class JoinStatusHandler implements SubscribingHandlerInterface
{
    private $user;

    public static function getSubscribingMethods()
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

    public function __construct(SecurityContextInterface $security)
    {
        $this->user = $security->getToken()->getUser();
    }

    public function serialize(JsonSerializationVisitor $visitor, JoinStatus $joinStatusType, array $type, Context $context)
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

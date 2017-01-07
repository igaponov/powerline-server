<?php
namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\User;
use JMS\Serializer\Context;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;

class OwnerHandler implements SubscribingHandlerInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Owner',
                'method' => 'serialize',
            ],
        ];
    }

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function serialize(JsonSerializationVisitor $visitor, User $user, array $type, Context $context)
    {
        $result = false;
        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof User) {
            $result = $user->isEqualTo($this->tokenStorage->getToken()->getUser());
        }

        return $visitor->visitBoolean($result, $type, $context);
    }
}
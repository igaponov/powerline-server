<?php
namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContextInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;

class OwnerHandler implements SubscribingHandlerInterface
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Owner',
                'method' => 'serialize',
            ),
        );
    }

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function serialize(JsonSerializationVisitor $visitor, User $user, array $type)
    {
        if ($this->securityContext->getToken() && $this->securityContext->getToken()->getUser() instanceof User) {
            return $user->isEqualTo($this->securityContext->getToken()->getUser());
        }

        return false;
    }
}
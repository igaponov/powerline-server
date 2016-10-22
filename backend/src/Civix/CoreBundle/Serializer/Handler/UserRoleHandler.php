<?php
namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Serializer\Type\UserRole;
use Symfony\Component\Security\Core\SecurityContextInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;

class UserRoleHandler implements SubscribingHandlerInterface
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
                'type' => 'UserRole',
                'method' => 'serialize',
            ),
        );
    }

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function serialize(JsonSerializationVisitor $visitor, UserRole $userRole, array $type)
    {
        if (!$this->securityContext->getToken() || !$this->securityContext->getToken()->getUser() instanceof User) {
            return '';
        }

        $user = $this->securityContext->getToken()->getUser();
        $group = $userRole->getGroup();
        if ($group->getOwner()->isEqualTo($user)) {
            return 'owner';
        } elseif ($group->getManagers()->contains($user)) {
            return 'manager';
        } elseif ($group->getUsers()->contains($user)) {
            return 'member';
        }

        return '';
    }
}
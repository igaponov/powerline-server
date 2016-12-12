<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Serializer\Type\Avatar;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use Civix\CoreBundle\Serializer\Type\OwnerData;

class OwnerDataHandler implements SubscribingHandlerInterface
{
    /**
     * @var AvatarHandler
     */
    private $avatarHandler;

    public function __construct(AvatarHandler $avatarHandler)
    {
        $this->avatarHandler = $avatarHandler;
    }

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'OwnerData',
                'method' => 'serialize',
            ),
        );
    }

    public function serialize(JsonSerializationVisitor $visitor, OwnerData $owner, array $type, Context $context)
    {
        $data = $owner->getData();
        $avatar = new Avatar($owner);
        $data['avatar_file_path'] = $this->avatarHandler->serialize($visitor, $avatar, $type, $context);

        return $data;
    }
}

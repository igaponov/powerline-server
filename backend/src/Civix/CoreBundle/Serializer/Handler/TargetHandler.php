<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Serializer\Type\Avatar;
use Civix\CoreBundle\Serializer\Type\Target;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\HttpFoundation\File\File;

class TargetHandler implements SubscribingHandlerInterface
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
                'type' => 'Target',
                'method' => 'serialize',
            ),
        );
    }

    public function serialize(JsonSerializationVisitor $visitor, Target $target, array $type, Context $context)
    {
        $data = $target->getData();
        if (!empty($data['image'])) {
            // inject a File for \Vich\UploaderBundle\Storage\StorageInterface::resolveUri
            $target->setAvatar(new File($data['image'], false));
        }
        $avatar = new Avatar($target);
        $data['image'] = $this->avatarHandler->serialize($visitor, $avatar, $type, $context);

        return $data;
    }
}

<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\HasAvatarInterface;
use Civix\CoreBundle\Entity\User;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\JsonDeserializationVisitor;
use Civix\CoreBundle\Serializer\Type\Avatar;

class AvatarHandler implements SubscribingHandlerInterface
{
    /**
     * @var ImageHandler
     */
    private $imageHandler;
    /**
     * @var string
     */
    private $hostname;
    /**
     * @var
     */
    private $scheme;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Avatar',
                'method' => 'serialize',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'Avatar',
                'method' => 'deserialize',
            ],
        ];
    }

    public function __construct(ImageHandler $imageHandler, $hostname, $scheme)
    {
        $this->imageHandler = $imageHandler;
        $this->hostname = $hostname;
        $this->scheme = $scheme;
    }

    public function serialize(JsonSerializationVisitor $visitor, Avatar $avatar, array $type, Context $context)
    {
        $scheme = $this->scheme ? $this->scheme.'://' : null;
        if (!$avatar->isPrivacy()) {
            /** @var HasAvatarInterface $entity */
            $entity = $avatar->getEntity();
            if ($entity->getAvatar()) {
                return $this->imageHandler->serialize($visitor, $avatar, $type, $context);
            } else {
                return $visitor->visitNull($entity->getAvatar(), $type, $context);
            }
        } else {
            return $visitor->visitString($scheme.$this->hostname.User::SOMEONE_AVATAR, $type, $context);
        }
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param $avatar
     * @param array $type
     * @param Context $context
     * @return null|string return base64 string or null
     */
    public function deserialize(JsonDeserializationVisitor $visitor, $avatar, array $type, Context $context)
    {
        return !preg_match('/^http/', $avatar) ? $visitor->visitString($avatar, $type, $context) : null;
    }
}

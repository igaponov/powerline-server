<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\HasAvatarInterface;
use Civix\CoreBundle\Entity\User;
use JMS\Serializer\Context;
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

    public static function getSubscribingMethods()
    {
    }

    public function __construct(ImageHandler $imageHandler, $hostname)
    {
        $this->imageHandler = $imageHandler;
        $this->hostname = $hostname;
    }

    public function serialize(JsonSerializationVisitor $visitor, Avatar $avatar, array $type, Context $context)
    {
        if (!$avatar->isPrivacy()) {
            /** @var HasAvatarInterface $entity */
            $entity = $avatar->getEntity();
            if ($entity->getAvatar()) {
                return $this->imageHandler->serialize($visitor, $avatar, $type, $context);
            } else {
                $url = $this->hostname.$entity->getDefaultAvatar();
            }
        } else {
            $url = $this->hostname.User::SOMEONE_AVATAR;
        }

        return $visitor->visitString($url, $type, $context);
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

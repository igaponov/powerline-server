<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Serializer\Type\Avatar;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use Civix\CoreBundle\Serializer\Type\OwnerData;
use JMS\Serializer\VisitorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'OwnerData',
                'method' => 'serialize',
            ],
        ];
    }

    public function serialize(VisitorInterface $visitor, OwnerData $owner, array $type, Context $context)
    {
        $data = $owner->getData();
        if (!empty($data['avatar_file_path'])) {
            // inject a File for \Vich\UploaderBundle\Storage\StorageInterface::resolveUri
            $owner->setAvatar(new UploadedFile($data['avatar_file_path'], uniqid(), null, null, 1));
        }
        $avatar = new Avatar($owner);
        $data['avatar_file_path'] = $this->avatarHandler->serialize($visitor, $avatar, $type, $context);

        return $visitor->visitArray($data, $type, $context);
    }
}

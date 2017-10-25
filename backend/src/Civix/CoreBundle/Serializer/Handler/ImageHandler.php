<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Imgix\UrlBuilder;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use Civix\CoreBundle\Serializer\Type\Image;
use JMS\Serializer\VisitorInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

class ImageHandler implements SubscribingHandlerInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Image',
                'method' => 'serialize',
            ],
        ];
    }

    public function __construct(
        StorageInterface $storage,
        UrlBuilder $urlBuilder
    )
    {
        $this->storage = $storage;
        $this->urlBuilder = $urlBuilder;
    }

    public function serialize(VisitorInterface $visitor, Image $image, array $type, Context $context)
    {
        if (!$image->isAvailable()) {
            return null;
        }
        if ($image->isUrl()) {
            $url = $image->getImageSrc();
        } elseif ($uri = $this->storage->resolveUri($image->getEntity(), $image->getField())) {
            $url = $this->urlBuilder->createURL($uri);
        } else {
            return null;
        }

        return $visitor->visitString($url, $type, $context);
    }
}

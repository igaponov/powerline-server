<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Imgix\UrlBuilder;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use Civix\CoreBundle\Serializer\Type\Image;
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
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Image',
                'method' => 'serialize',
            ),
        );
    }

    public function __construct(
        StorageInterface $storage,
        UrlBuilder $urlBuilder
    )
    {
        $this->storage = $storage;
        $this->urlBuilder = $urlBuilder;
    }

    public function serialize(JsonSerializationVisitor $visitor, Image $image, array $type, Context $context)
    {
        if (!$image->isAvailable()) {
            return null;
        }
        if ($image->isUrl()) {
            $url = $image->getImageSrc();
        } else {
            $uri = $this->storage->resolveUri($image->getEntity(), $image->getField());
            $url = $this->urlBuilder->createURL($uri);
        }

        return $visitor->visitString($url, $type, $context);
    }
}

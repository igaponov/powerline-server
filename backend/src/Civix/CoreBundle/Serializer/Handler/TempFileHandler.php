<?php

namespace Civix\CoreBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Routing\RouterInterface;

class TempFileHandler implements SubscribingHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'TempFile',
                'method' => 'serialize',
            ],
        ];
    }

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function serialize(JsonSerializationVisitor $visitor, $id, array $type, Context $context)
    {
        $url = $this->router->generate('civix_api_public_file', ['id' => $id]);

        return $visitor->visitString($url, $type, $context);
    }
}

<?php
namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Serializer\Type\Karma;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;

class KarmaHandler implements SubscribingHandlerInterface
{
    /**
     * @var UserRepository
     */
    private $repository;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'Karma',
                'method' => 'serialize',
            ],
        ];
    }

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function serialize(JsonSerializationVisitor $visitor, Karma $karma, array $type, Context $context)
    {
        $value = $this->repository->getUserKarma($karma->getUser());

        return $visitor->visitInteger($value, $type, $context);
    }
}
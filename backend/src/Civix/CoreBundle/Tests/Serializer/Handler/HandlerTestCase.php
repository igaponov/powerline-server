<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\ApiBundle\Tests\WebTestCase;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\SerializationContext;
use Metadata\MetadataFactory;

abstract class HandlerTestCase extends WebTestCase
{
    /**
     * @return callable
     */
    abstract protected function getHandler();

    protected function assertSerialization($expected, $data, $message = '')
    {
        $handler = $this->getHandler();
        $this->assertInternalType('callable', $handler);
        $container = $this->getContainer();
        $visitor = $container->get('jms_serializer.json_serialization_visitor');
        $driver = $container->get('jms_serializer.metadata_driver');
        $factory = new MetadataFactory($driver);
        $navigator = new GraphNavigator(
            $factory,
            $container->get('jms_serializer.handler_registry'),
            $container->get('jms_serializer.object_constructor'),
            $this->getMockBuilder(EventDispatcherInterface::class)->getMock()
        );
        $visitor->setNavigator($navigator);
        $context = new SerializationContext();
        $context->initialize(
            'json',
            $visitor,
            $navigator,
            $factory
        );
        $this->assertEquals($expected, call_user_func($handler, $visitor, $data, [], $context), $message);
    }
}
<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Serializer\Type\Target;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;

class TargetHandlerTest extends WebTestCase
{
    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $handler = $this->getContainer()->get('civix_core.serializer.handler.target_handler');
        $target = new Target(['image' => $avatar]);
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            [
                'image' => "http://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            ],
            $handler->serialize($visitor, $target, [], new SerializationContext())
        );
    }

    public function testSerializeDefault()
    {
        $handler = $this->getContainer()->get('civix_core.serializer.handler.target_handler');
        $target = new Target();
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            [
                'image' => $this->getContainer()->getParameter('hostname').User::SOMEONE_AVATAR,
            ],
            $handler->serialize($visitor, $target, [], new SerializationContext())
        );
    }
}
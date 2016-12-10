<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;

class AvatarHandlerTest extends WebTestCase
{
    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $handler = $this->getContainer()->get('civix_core.serializer.handler.avatar_handler');
        $user = new User();
        $user->setAvatarFileName($avatar);
        $user->setAvatar($avatar);
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            "http://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            $handler->serialize($visitor, $user->getAvatarWithPath(), [], new SerializationContext())
        );
    }

    public function testSerializePrivate()
    {
        $avatar = 'avatar1.jpeg';
        $handler = $this->getContainer()->get('civix_core.serializer.handler.avatar_handler');
        $user = new User();
        $user->setAvatarFileName($avatar);
        $user->setAvatar($avatar);
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            $this->getContainer()->getParameter('hostname').User::SOMEONE_AVATAR,
            $handler->serialize($visitor, $user->getAvatarWithPath(true), [], new SerializationContext())
        );
    }

    public function testSerializeDefault()
    {
        $handler = $this->getContainer()->get('civix_core.serializer.handler.avatar_handler');
        $user = new User();
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            $this->getContainer()->getParameter('hostname').User::DEFAULT_AVATAR,
            $handler->serialize($visitor, $user->getAvatarWithPath(), [], new SerializationContext())
        );
    }
}
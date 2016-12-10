<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageHandlerTest extends WebTestCase
{
    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $handler = $this->getContainer()->get('civix_core.serializer.handler.image_handler');
        $user = new User();
        $user->setAvatarFileName($avatar);
        $user->setAvatar(new UploadedFile(__FILE__, '123'));
        $post = new Post();
        $post->setUser($user);
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            "http://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            $handler->serialize($visitor, $post->getSharePicture(), [], new SerializationContext())
        );
    }
}
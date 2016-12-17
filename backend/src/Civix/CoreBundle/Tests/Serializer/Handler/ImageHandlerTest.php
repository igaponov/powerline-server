<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageHandlerTest extends HandlerTestCase
{
    protected function getHandler()
    {
        return [
            $this->getContainer()->get('civix_core.serializer.handler.image_handler'),
            'serialize'
        ];
    }

    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $user = new User();
        $user->setAvatarFileName($avatar);
        $user->setAvatar(new UploadedFile(__FILE__, '123'));
        $post = new Post();
        $post->setUser($user);
        $this->assertSerialization(
            "https://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            $post->getSharePicture()
        );
    }
}
<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AvatarHandlerTest extends HandlerTestCase
{
    protected function getHandler()
    {
        return [
            $this->getContainer()->get('civix_core.serializer.handler.avatar_handler'),
            'serialize'
        ];
    }

    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $user = new User();
        $user->setAvatarFileName($avatar);
        $user->setAvatar(new UploadedFile(__FILE__, uniqid()));
        $this->assertSerialization(
            "https://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            $user->getAvatarWithPath()
        );
    }

    public function testSerializePrivate()
    {
        $avatar = 'avatar1.jpeg';
        $user = new User();
        $user->setAvatarFileName($avatar);
        $user->setAvatar(new UploadedFile(__FILE__, uniqid()));
        $this->assertSerialization(
            'http://'.$this->getContainer()->getParameter('hostname').User::SOMEONE_AVATAR,
            $user->getAvatarWithPath(true)
        );
    }

    public function testSerializeDefault()
    {
        $user = new User();
        $this->assertSerialization(
            'http://'.$this->getContainer()->getParameter('hostname').User::DEFAULT_AVATAR,
            $user->getAvatarWithPath()
        );
    }
}
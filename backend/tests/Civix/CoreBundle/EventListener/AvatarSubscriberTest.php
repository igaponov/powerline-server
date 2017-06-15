<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\EventListener\AvatarSubscriber;
use Civix\CoreBundle\Model\TempFile;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AvatarSubscriberTest extends TestCase
{
    public function testHandleAvatarWithInvalidImage()
    {
        $image = $this->getMockBuilder(Image::class)
            ->setMethods(['resize'])
            ->getMock();
        $image->expects($this->once())
            ->method('resize')
            ->willThrowException(new NotReadableException());
        $manager = $this->getImageManagerMock();
        $manager->expects($this->once())
            ->method('make')
            ->willReturn($image);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Uploading file error.', $this->anything());
        $subscriber = new AvatarSubscriber($manager, $logger);
        $user = new User();
        $user->setFirstName('John')
            ->setAvatar(new UploadedFile(__FILE__, uniqid('', true)));
        $event = new AvatarEvent($user);
        $subscriber->handleAvatar($event);
        $this->assertInstanceOf(TempFile::class, $user->getAvatar());
    }

    public function testHandleAvatarWithInvalidImageAndExistentAvatar()
    {
        $image = $this->getMockBuilder(Image::class)
            ->setMethods(['resize'])
            ->getMock();
        $image->expects($this->once())
            ->method('resize')
            ->willThrowException(new NotReadableException());
        $manager = $this->getImageManagerMock();
        $manager->expects($this->once())
            ->method('make')
            ->willReturn($image);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Uploading file error.', $this->anything());
        $subscriber = new AvatarSubscriber($manager, $logger);
        $user = new User();
        $user->setFirstName('John')
            ->setAvatar(new UploadedFile(__FILE__, uniqid('', true)))
            ->setAvatarFileName('http://example.com/avatar.jpg');
        $event = new AvatarEvent($user);
        $subscriber->handleAvatar($event);
        $this->assertNull($user->getAvatar());
    }

    /**
     * @return ImageManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getImageManagerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(ImageManager::class)
            ->setMethods(['make'])
            ->getMock();
    }
}

<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\Component\ThumbnailGenerator\ThumbnailGeneratorInterface;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\EventListener\ThumbnailSubscriber;
use Intervention\Image\Image;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Handler\UploadHandler;

class ThumbnailSubscriberTest extends TestCase
{
    public function testCreatePostFacebookTemplate()
    {
        $post = new Post();
        $event = new PostEvent($post);
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->setMethods(['encode', 'getEncoded'])
            ->getMock();
        $image->expects($this->once())
            ->method('encode')
            ->with('png', 100);
        $image->expects($this->once())
            ->method('getEncoded')
            ->with()
            ->willReturn('image png post content');
        $generator = $this->createMock(ThumbnailGeneratorInterface::class);
        $generator->expects($this->once())
            ->method('generate')
            ->with($post)
            ->willReturn($image);
        $handler = $this->getUploadHandlerMock(['upload']);
        $handler->expects($this->once())
            ->method('upload')
            ->with($this->callback(function (Post $post) {
                $this->assertSame(22, $post->getFacebookThumbnail()->getFile()->getSize());

                return true;
            }), 'facebookThumbnail.file');
        $subscriber = new ThumbnailSubscriber($generator, $handler);
        $subscriber->createPostFacebookThumbnail($event);
    }

    public function testCreatePetitionFacebookTemplate()
    {
        $petition = new UserPetition();
        $event = new UserPetitionEvent($petition);
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->setMethods(['encode', 'getEncoded'])
            ->getMock();
        $image->expects($this->once())
            ->method('encode')
            ->with('png', 100);
        $image->expects($this->once())
            ->method('getEncoded')
            ->with()
            ->willReturn('image png petition content');
        $generator = $this->createMock(ThumbnailGeneratorInterface::class);
        $generator->expects($this->once())
            ->method('generate')
            ->with($petition)
            ->willReturn($image);
        $handler = $this->getUploadHandlerMock(['upload']);
        $handler->expects($this->once())
            ->method('upload')
            ->with($this->callback(function (UserPetition $post) {
                $this->assertSame(26, $post->getFacebookThumbnail()->getFile()->getSize());

                return true;
            }), 'facebookThumbnail.file');
        $subscriber = new ThumbnailSubscriber($generator, $handler);
        $subscriber->createPetitionFacebookThumbnail($event);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UploadHandler
     */
    private function getUploadHandlerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(UploadHandler::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}

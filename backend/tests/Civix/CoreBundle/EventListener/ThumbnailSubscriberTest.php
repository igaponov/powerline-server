<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\Component\ThumbnailGenerator\ThumbnailGeneratorInterface;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\EventListener\ThumbnailSubscriber;
use Intervention\Image\Image;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Handler\UploadHandler;

class ThumbnailSubscriberTest extends TestCase
{
    public function testCreateFacebookTemplate()
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
            ->willReturn('image png content');
        $converter = $this->createMock(ThumbnailGeneratorInterface::class);
        $converter->expects($this->once())
            ->method('convert')
            ->with($post)
            ->willReturn($image);
        $handler = $this->getUploadHandlerMock(['upload']);
        $handler->expects($this->once())
            ->method('upload')
            ->with($this->callback(function (Post $post) {
                $this->assertSame(17, $post->getFacebookThumbnail()->getFile()->getSize());

                return true;
            }), 'facebookThumbnail');
        $subscriber = new ThumbnailSubscriber($converter, $handler);
        $subscriber->createFacebookThumbnail($event);
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

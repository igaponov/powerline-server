<?php

namespace Tests\Civix\Component\ThumbnailGenerator;

use Civix\Component\ThumbnailGenerator\ThumbnailGenerator;
use Civix\Component\ThumbnailGenerator\GeneratorCollection;
use Civix\Component\ThumbnailGenerator\NormalizerCollection;
use Civix\Component\ThumbnailGenerator\ObjectThumbnailGeneratorInterface;
use Civix\Component\ThumbnailGenerator\ObjectNormalizerInterface;
use Intervention\Image\Image;
use PHPUnit\Framework\TestCase;

class ThumbnailGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $object = new \stdClass();
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->getMock();
        $normalizer = $this->createMock(ObjectNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('supports')
            ->with($object)
            ->willReturn(true);
        $normalizer->expects($this->once())
            ->method('normalize')
            ->with($object)
            ->willReturnArgument(0);
        $converter = $this->createMock(ObjectThumbnailGeneratorInterface::class);
        $converter->expects($this->once())
            ->method('supports')
            ->with($object)
            ->willReturn(true);
        $converter->expects($this->once())
            ->method('generate')
            ->with($object)
            ->willReturn($image);
        $normalizerCollection = new NormalizerCollection($normalizer);
        $converterCollection = new GeneratorCollection($converter);
        $converter = new ThumbnailGenerator($normalizerCollection, $converterCollection);
        $converter->generate($object);
    }
}

<?php

namespace Tests\Civix\CoreBundle\Service\ThumbnailGenerator;

use Civix\CoreBundle\Model\FacebookContent;
use Civix\CoreBundle\Service\ThumbnailGenerator\FacebookThumbnailGenerator;
use Civix\CoreBundle\Service\ThumbnailGenerator\FacebookThumbnailGeneratorConfig;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;

class FacebookThumbnailGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $manager = new ImageManager();
        $fontPath = __DIR__.'/../../../../../src/Civix/CoreBundle/Resources/public/fonts/';
        $imgPath = __DIR__.'/../../../../../src/Civix/CoreBundle/Resources/public/img/';
        $config = new FacebookThumbnailGeneratorConfig(
            $fontPath.'montserrat_regular.ttf',
            $fontPath.'montserrat_bold.ttf',
            $fontPath.'montserrat_italic.ttf',
            $fontPath.'open_sans_regular.ttf',
            $imgPath.'jc_logo.png',
            $imgPath.'p_logo_watermark.png'
        );
        $converter = new FacebookThumbnailGenerator($manager, $config);
        $content = new FacebookContent(
            'John Doe',
            'johndoe',
            __DIR__.'/../../../../data/avatar.jpg',
            'Test Group Name',
            __DIR__.'/../../../../data/blacklivesmatter.jpg',
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla consectetur magna lacinia placerat aliquam. Phasellus at nisi ac nibh placerat fermentum. Nulla nec scelerisque ex, quis egestas tellus. Cras commodo, tellus sit amet ultricies efficitur, tellus ex pretium tellus, quis dictum lacus libero sed risus. Nunc in ante ornare, aliquet elit vel, sodales purus. Suspendisse fermentum dignissim ipsum, et laoreet massa auctor vel. Nam molestie nunc luctus arcu efficitur mattis.'
        );
        $image = $converter->generate($content);
        $image->encode('png');
        $expected = $manager->make(__DIR__.'/../../../../data/fb.png');
        $this->assertSame($expected->height(), $image->height());
        $this->assertSame($expected->width(), $image->width());
        // compare interesting points
        $points = [
            [23, 23],
            [97, 32],
            [143, 63],
            [430, 135],
            [455, 45],
            [375, 382],
            [378, 374],
            [362, 386],
        ];
        foreach ($points as [$x, $y]) {
            $this->assertSame(
                $expected->pickColor($x, $y, 'hex'),
                $image->pickColor($x, $y, 'hex'),
                sprintf('Invalid color at %d,%d', $x, $y)
            );
        }
    }
}

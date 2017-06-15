<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\CoreBundle\Serializer\Type\Target;

class TargetHandlerTest extends HandlerTestCase
{
    protected function getHandler()
    {
        return [
            $this->getContainer()->get('civix_core.serializer.handler.target_handler'),
            'serialize'
        ];
    }

    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $target = new Target(['image' => $avatar]);
        $this->assertSerialization(
            [
                'image' => "https://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            ],
            $target
        );
    }

    public function testSerializeDefault()
    {
        $target = new Target();
        $this->assertSerialization(
            [],
            $target
        );
    }
}
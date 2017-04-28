<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\CoreBundle\Serializer\Type\OwnerData;

class OwnerDataHandlerTest extends HandlerTestCase
{
    protected function getHandler()
    {
        return [
            $this->getContainer()->get('civix_core.serializer.handler.owner_data_handler'),
            'serialize'
        ];
    }

    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $ownerData = new OwnerData(['type' => 'user', 'avatar_file_path' => $avatar]);
        $this->assertSerialization(
            [
                'type' => 'user',
                'avatar_file_path' => "https://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            ],
            $ownerData
        );
    }

    /**
     * @param $type
     * @dataProvider getOwnerTypes
     */
    public function testSerializeDefault($type)
    {
        $ownerData = new OwnerData(['type' => $type]);
        $this->assertSerialization(
            [
                'type' => $type,
            ],
            $ownerData
        );
    }

    public function getOwnerTypes()
    {
        return [
            'user' => ['user'],
            'deleted' => ['deleted'],
            'group' => ['group'],
            'superuser' => ['admin'],
        ];
    }
}
<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Entity\User;
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
     * @param $avatar
     * @param $type
     * @dataProvider getOwnerTypes
     */
    public function testSerializeDefault($avatar, $type)
    {
        $ownerData = new OwnerData(['type' => $type]);
        $this->assertSerialization(
            [
                'type' => $type,
                'avatar_file_path' => 'http://'.$this->getContainer()->getParameter('hostname').$avatar,
            ],
            $ownerData
        );
    }

    public function getOwnerTypes()
    {
        return [
            'user' => [User::DEFAULT_AVATAR, 'user'],
            'deleted' => [User::DEFAULT_AVATAR, 'deleted'],
            'group' => [Group::DEFAULT_AVATAR, 'group'],
            'superuser' => [Superuser::DEFAULT_AVATAR, 'admin'],
        ];
    }
}
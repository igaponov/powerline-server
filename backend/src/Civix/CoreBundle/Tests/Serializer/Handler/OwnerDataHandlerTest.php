<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Serializer\Type\OwnerData;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;

class OwnerDataHandlerTest extends WebTestCase
{
    public function testSerialize()
    {
        $avatar = 'avatar1.jpeg';
        $handler = $this->getContainer()->get('civix_core.serializer.handler.owner_data_handler');
        $ownerData = new OwnerData(['type' => 'user', 'avatar_file_path' => $avatar]);
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            [
                'type' => 'user',
                'avatar_file_path' => "https://powerline-dev.imgix.net/avatars/$avatar?ixlib=php-1.1.0",
            ],
            $handler->serialize($visitor, $ownerData, [], new SerializationContext())
        );
    }

    /**
     * @param $avatar
     * @param $type
     * @dataProvider getOwnerTypes
     */
    public function testSerializeDefault($avatar, $type)
    {
        $handler = $this->getContainer()->get('civix_core.serializer.handler.owner_data_handler');
        $ownerData = new OwnerData(['type' => $type]);
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $this->getMockBuilder(JsonSerializationVisitor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            [
                'type' => $type,
                'avatar_file_path' => 'http://'.$this->getContainer()->getParameter('hostname').$avatar,
            ],
            $handler->serialize($visitor, $ownerData, [], new SerializationContext())
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
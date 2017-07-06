<?php
namespace Civix\CoreBundle\Tests\Serializer\Handler;

use Civix\CoreBundle\Serializer\Handler\AvatarHandler;
use Civix\CoreBundle\Serializer\Handler\OwnerDataHandler;
use Civix\CoreBundle\Serializer\Type\GroupOwnerData;
use Civix\CoreBundle\Serializer\Type\OwnerData;
use Civix\CoreBundle\Serializer\Type\RepresentativeOwnerData;
use Civix\CoreBundle\Serializer\Type\UserOwnerData;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;

class OwnerDataHandlerTest extends TestCase
{
    /**
     * @param $ownerData
     * @dataProvider getOwnerData
     */
    public function testSerialize(OwnerData $ownerData)
    {
        $url = 'avatars/'.$ownerData->getAvatarFileName();
        $avatarHandler = $this->getAvatarHandlerMock();
        $handler = new OwnerDataHandler($avatarHandler);
        $context = new SerializationContext();
        $visitor = $this->getVisitorMock();
        $visitor->expects($this->once())
            ->method('visitArray')
            ->willReturnArgument(0);
        $avatarHandler->expects($this->once())
            ->method('serialize')
            ->willReturn($url);
        $json = $handler->serialize($visitor, $ownerData, [], $context);
        $this->assertSame(
            array_replace($ownerData->getData(), ['avatar_file_path' => $url]),
            $json
        );
    }

    public function getOwnerData(): array
    {
        $avatar = 'avatar1.jpeg';
        return [
            'default' => [new OwnerData(['type' => 'deleted', 'avatar_file_path' => $avatar])],
            'user' => [new UserOwnerData(['type' => 'user', 'avatar_file_path' => $avatar])],
            'group' => [new GroupOwnerData(['type' => 'group', 'avatar_file_path' => $avatar])],
            'representative' => [new RepresentativeOwnerData(['type' => 'representative', 'avatar_file_path' => $avatar])],
        ];
    }

    /**
     * @return VisitorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getVisitorMock()
    {
        return $this->createMock(VisitorInterface::class);
    }

    /**
     * @return AvatarHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getAvatarHandlerMock()
    {
        return $this->getMockBuilder(AvatarHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
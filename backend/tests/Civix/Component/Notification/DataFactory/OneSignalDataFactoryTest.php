<?php

namespace Tests\Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\DataFactory\OneSignalDataFactory;
use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\Model\RecipientInterface;
use Civix\Component\Notification\PushMessage;
use PHPUnit\Framework\TestCase;

class OneSignalDataFactoryTest extends TestCase
{
    public function testMessage()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $device = new Device($recipient);
        $message = new PushMessage($recipient, 'test_title', 'test_message', 'test_type');
        $message->setBadge(5);
        $factory = new OneSignalDataFactory();
        $this->assertEquals(
            [
                'include_player_ids' => [null],
                'data' => [
                    'type' => 'test_type',
                    'image' => '',
                    'user' => [
                        'id' => null,
                        'username' => null,
                    ],
                ],
                'contents' => [
                    'en' => 'test_message',
                ],
                'headings' => [
                    'en' => 'test_title',
                ],
                'isIos' => false,
                'isAndroid' => false,
                'large_icon' => '',
                'buttons' => [],
                'ios_category' => 'test_type',
                'ios_badgeCount' => 5,
                'ios_badgeType' => 'SetTo',
                'ios_sound' => 'default',
            ],
            $factory->createData($message, $device)
        );
    }
}
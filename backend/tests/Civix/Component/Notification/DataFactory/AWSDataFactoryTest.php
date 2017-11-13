<?php

namespace Tests\Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\DataFactory\AWSDataFactory;
use Civix\Component\Notification\Model\AndroidEndpoint;
use Civix\Component\Notification\Model\IOSEndpoint;
use Civix\Component\Notification\Model\RecipientInterface;
use Civix\Component\Notification\PushMessage;
use PHPUnit\Framework\TestCase;

class AWSDataFactoryTest extends TestCase
{
    /**
     * @group notification
     */
    public function testAndroidMessage()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $endpoint = new AndroidEndpoint();
        $message = new PushMessage($recipient, 'test_title', 'test_message', 'test_type');
        $message->setBadge(5);
        $factory = new AWSDataFactory();
        $this->assertEquals(
            [
                'GCM' => '{"data":{"message":"test_message","type":"test_type","entity":[],"title":"test_title","image":"","actions":[],"badge":5,"additionalData":{"badgeCount":5}}}',
            ],
            $factory->createData($message, $endpoint)
        );
    }

    /**
     * @group notification
     */
    public function testIOSMessage()
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $endpoint = new IOSEndpoint;
        $message = new PushMessage($recipient, 'test_title', 'test_message', 'test_type');
        $message->setBadge(5);
        $factory = new AWSDataFactory();
        $this->assertRegExp(
            '/{"default":"test_message","APNS":"{\\\\"aps\\\\":{\\\\"alert\\\\":{\\\\"title\\\\":\\\\"test_title\\\\",\\\\"body\\\\":\\\\"test_message\\\\"},\\\\"entity\\\\":\[\],\\\\"type\\\\":\\\\"test_type\\\\",\\\\"category\\\\":\\\\"test_type\\\\",\\\\"sound\\\\":\\\\"default\\\\",\\\\"title\\\\":\\\\"test_title\\\\",\\\\"image\\\\":\\\\"\\\\",\\\\"badge\\\\":5,\\\\"additionalData\\\\":{\\\\"badgeCount\\\\":5,\\\\"notId\\\\":\\\\"[\d\w]+\.\d+\\\\"}},\\\\"notId\\\\":\\\\"[\d\w]+.\d+\\\\"}","APNS_SANDBOX":"{\\\\"aps\\\\":{\\\\"alert\\\\":{\\\\"title\\\\":\\\\"test_title\\\\",\\\\"body\\\\":\\\\"test_message\\\\"},\\\\"entity\\\\":\[\],\\\\"type\\\\":\\\\"test_type\\\\",\\\\"category\\\\":\\\\"test_type\\\\",\\\\"sound\\\\":\\\\"default\\\\",\\\\"title\\\\":\\\\"test_title\\\\",\\\\"image\\\\":\\\\"\\\\",\\\\"badge\\\\":5,\\\\"additionalData\\\\":{\\\\"badgeCount\\\\":5,\\\\"notId\\\\":\\\\"[\d\w]+.\d+\\\\"}},\\\\"notId\\\\":\\\\"[\d\w]+.\d+\\\\"}"}/',
            json_encode($factory->createData($message, $endpoint))
        );
    }
}
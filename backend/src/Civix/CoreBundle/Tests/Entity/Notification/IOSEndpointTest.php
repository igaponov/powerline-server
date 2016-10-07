<?php

namespace Civix\CoreBundle\Tests\Entity\Notification;

use Civix\CoreBundle\Entity\Notification\IOSEndpoint;

class IOSEndpointTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group notification
     */
    public function testPlatformMessage()
    {
        $endpoint = new IOSEndpoint;
        $this->assertEquals(
            '{"default":"test_message","APNS":"{\"aps\":{\"alert\":{\"title\":\"test_message\",\"body\":\"test_message\"},\"entity\":\"null\",\"type\":\"test_type\",\"category\":\"test_type\",\"sound\":\"default\",\"title\":\"test_title\",\"image\":null,\"badge\":5,\"additionalData\":{\"badgeCount\":5}}}","APNS_SANDBOX":"{\"aps\":{\"alert\":{\"title\":\"test_message\",\"body\":\"test_message\"},\"entity\":\"null\",\"type\":\"test_type\",\"category\":\"test_type\",\"sound\":\"default\",\"title\":\"test_title\",\"image\":null,\"badge\":5,\"additionalData\":{\"badgeCount\":5}}}"}',
            $endpoint->getPlatformMessage('test_title', 'test_message', 'test_type', null, null, 5)
        );
    }
}

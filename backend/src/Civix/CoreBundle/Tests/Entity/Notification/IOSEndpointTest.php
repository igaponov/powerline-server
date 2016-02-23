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
        $this->assertEquals($endpoint->getPlatformMessage('test_title', 'test_message', 'test_type', null, null),
            '{"APNS":"{\"aps\":{\"alert\":\"test_message\",\"entity\":\"null\",'.
            '\"type\":\"test_type\",\"sound\":\"default\",\"title\":\"test_title\",\"image\":null}}"}'
        );
    }
}

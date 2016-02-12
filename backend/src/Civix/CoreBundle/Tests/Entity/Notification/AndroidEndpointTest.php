<?php

namespace Civix\CoreBundle\Tests\Entity\Notification;

use Civix\CoreBundle\Entity\Notification\AndroidEndpoint;

class AndroidEndpointTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group notification
     */
    public function testPlatformMessage()
    {
        $endpoint = new AndroidEndpoint();
        $this->assertEquals($endpoint->getPlatformMessage('test_message', 'test_type', null),
            '{"GCM":"{\"data\":{\"message\":\"test_message\",\"type\":\"test_type\",\"entity\":\"null\"}}"}'
        );
    }
}

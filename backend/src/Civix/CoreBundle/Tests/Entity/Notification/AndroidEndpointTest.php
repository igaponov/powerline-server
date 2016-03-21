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
    	$this->markTestIncomplete('Waiting for several info from mobile developer');
    	
        $endpoint = new AndroidEndpoint;
        $data = $endpoint->getPlatformMessage('test_title', 'test_message', 'test_type', null, null);

        $this->assertEquals(
            $data,
            '{"GCM":"{\"data\":{\"message\":\"test_message\",\"type\":\"test_type\",\"entity\":\"null\",\"title\":\"test_title\",\"image\":null,\"actions\":[]}}"}'
        );
    }
}

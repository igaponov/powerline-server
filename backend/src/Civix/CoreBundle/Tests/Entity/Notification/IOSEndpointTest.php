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
        $this->assertRegExp(
            '/{"default":"test_message","APNS":"{\\\\"aps\\\\":{\\\\"alert\\\\":{\\\\"title\\\\":\\\\"test_title\\\\",\\\\"body\\\\":\\\\"test_message\\\\"},\\\\"entity\\\\":null,\\\\"type\\\\":\\\\"test_type\\\\",\\\\"category\\\\":\\\\"test_type\\\\",\\\\"sound\\\\":\\\\"default\\\\",\\\\"title\\\\":\\\\"test_title\\\\",\\\\"image\\\\":null,\\\\"badge\\\\":5,\\\\"additionalData\\\\":{\\\\"badgeCount\\\\":5,\\\\"notId\\\\":\\\\"[\d\w]+\.\d+\\\\"}},\\\\"notId\\\\":\\\\"[\d\w]+.\d+\\\\"}","APNS_SANDBOX":"{\\\\"aps\\\\":{\\\\"alert\\\\":{\\\\"title\\\\":\\\\"test_title\\\\",\\\\"body\\\\":\\\\"test_message\\\\"},\\\\"entity\\\\":null,\\\\"type\\\\":\\\\"test_type\\\\",\\\\"category\\\\":\\\\"test_type\\\\",\\\\"sound\\\\":\\\\"default\\\\",\\\\"title\\\\":\\\\"test_title\\\\",\\\\"image\\\\":null,\\\\"badge\\\\":5,\\\\"additionalData\\\\":{\\\\"badgeCount\\\\":5,\\\\"notId\\\\":\\\\"[\d\w]+.\d+\\\\"}},\\\\"notId\\\\":\\\\"[\d\w]+.\d+\\\\"}"}/',
            $endpoint->getPlatformMessage('test_title', 'test_message', 'test_type', null, null, 5)
        );
    }
}

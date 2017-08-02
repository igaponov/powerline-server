<?php

namespace Tests\Civix\Component\Notification\Retriever;

use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\Retriever\ObjectDeviceRetriever;

class ObjectDeviceRetrieverTest extends ObjectRetrieverTestCase
{
    public function testRetrieve()
    {
        $this->retrieve(Device::class, Device::class, ObjectDeviceRetriever::class);
    }
}

<?php

namespace Tests\Civix\Component\Notification\Retriever;

use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\Model\AndroidEndpoint;
use Civix\Component\Notification\Model\IOSEndpoint;
use Civix\Component\Notification\Retriever\ObjectEndpointRetriever;

class ObjectEndpointRetrieverTest extends ObjectRetrieverTestCase
{
    /**
     * @param string $class
     * @dataProvider getEndpoints
     */
    public function testRetrieve(string $class)
    {
        $this->retrieve($class, AbstractEndpoint::class, ObjectEndpointRetriever::class);
    }

    public function getEndpoints()
    {
        return [
            [AndroidEndpoint::class],
            [IOSEndpoint::class],
        ];
    }
}

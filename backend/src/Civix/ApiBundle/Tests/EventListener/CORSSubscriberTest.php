<?php

namespace Civix\ApiBundle\Tests\EventListener;

use FOS\RestBundle\Util\Codes;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class CORSSubscriberTest extends WebTestCase
{
    /**
     * @group api
     * @group cors
     */
    public function testGET()
    {
        $client = static::createClient(array(), array('HTTPS' => true));
        $client->request('GET', '/api-public/users/');
        $this->assertEquals(
            Codes::HTTP_OK,
            $client->getResponse()->getStatusCode(),
            'Should return content'
        );
        $this->assertNotEmpty($client->getResponse()->headers->get('Access-Control-Allow-Origin'),
            'Should return cors headers');
    }

    /**
     * @group api
     * @group cors
     */
    public function testNotAllowedMethod()
    {
        $client = static::makeClient(false, array(
            'HTTPS' => true
        ));
        $client->request('POST', '/api-public/users/');
        $response = $client->getResponse();
        $this->assertEquals(
            Codes::HTTP_METHOD_NOT_ALLOWED,
            $response->getStatusCode(),
            $response->getContent()
        );
        $this->assertNotEmpty(
            $response->headers->get('Access-Control-Allow-Origin'),
            'Should return cors headers');
    }

    /**
     * @group api
     * @group cors
     */
    public function testOPTIONS()
    {
        $client = static::createClient(array(), array('HTTPS' => true));

        $client->request('OPTIONS', '/api/activity');
        $this->assertEquals(
            Codes::HTTP_OK,
            $client->getResponse()->getStatusCode(),
            'Should return 200 ok'
        );
        $this->assertNotEmpty($client->getResponse()->headers->get('Access-Control-Allow-Origin'),
            'Should return cors headers');
    }
}

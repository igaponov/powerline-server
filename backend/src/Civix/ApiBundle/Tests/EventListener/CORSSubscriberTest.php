<?php

namespace Civix\ApiBundle\Tests\EventListener;

use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CORSSubscriberTest extends WebTestCase
{
    /**
     * @group api
     * @group cors
     */
    public function testGET()
    {
        $client = static::createClient(array(), array(
            'HTTPS' => true,
            'HTTP_ORIGIN' => 'https://powerli.ne',
        ));
        $client->request('GET', '/api-public/users/');
        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(),
            'Should return content'
        );
        $this->assertNotEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Origin'),
            'Should return cors headers'
        );
        $this->assertNotEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Headers'),
            'Should return cors headers'
        );
        $this->assertEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Methods'),
            'Should not return cors methods'
        );
    }

    /**
     * @group api
     * @group cors
     */
    public function testNotAllowedMethod()
    {
        $client = static::makeClient(false, array(
            'HTTPS' => true,
            'HTTP_ORIGIN' => 'https://powerli.ne',
        ));
        $client->request('POST', '/api-public/users/');
        $response = $client->getResponse();
        $this->assertEquals(
            Response::HTTP_METHOD_NOT_ALLOWED,
            $response->getStatusCode(),
            $response->getContent()
        );
        $this->assertNotEmpty(
            $response->headers->get('Access-Control-Allow-Origin'),
            'Should return cors origin'
        );
        $this->assertNotEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Headers'),
            'Should return cors headers'
        );
        $this->assertEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Methods'),
            'Should not return cors methods'
        );
    }

    /**
     * @group api
     * @group cors
     */
    public function testOPTIONS()
    {
        $client = static::createClient(array(), array(
            'HTTPS' => true,
            'HTTP_ORIGIN' => 'https://powerli.ne',
        ));

        $client->request('OPTIONS', '/api/activity');
        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(),
            'Should return 200 ok'
        );
        $this->assertNotEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Origin'),
            'Should return cors origin'
        );
        $this->assertNotEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Headers'),
            'Should return cors headers'
        );
        $this->assertNotEmpty(
            $client->getResponse()->headers->get('Access-Control-Allow-Methods'),
            'Should return cors methods'
        );
    }
}

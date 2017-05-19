<?php
namespace Civix\ApiBundle\Tests\Controller;

use Aws\Sns\SnsClient;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadEndpointData;
use Symfony\Bundle\FrameworkBundle\Client;

class EndpointControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/endpoints/';

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testGetEndpoints()
    {
        $this->loadFixtures([
            LoadEndpointData::class,
        ]);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        foreach ($data as $item) {
            $this->assertCount(3, $item);
            $this->assertNotEmpty($item['id']);
            $this->assertNotEmpty($item['token']);
            $this->assertNotEmpty($item['type']);
        }
    }

    public function testCreateEndpoints()
    {
        $this->loadFixtures([
            LoadEndpointData::class,
        ]);
        $params = [
            'type' => 'android',
            'token' => 'jHJBv1e97rBfK9xG1JVC4xB9h8XTPuv',
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(SnsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPlatformEndpoint'])
            ->getMock();
        $service->expects($this->once())
            ->method('createPlatformEndpoint')
            ->willReturn(['EndpointArn' => 'arn:zzz']);
        $client->getContainer()->set('aws.sns', $service);
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertEquals($params['token'], $data['token']);
        $this->assertNotEmpty($params['type'], $data['type']);
    }
}
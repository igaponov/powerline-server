<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Aws\Sns\SnsClient;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadEndpointData;
use Symfony\Bundle\FrameworkBundle\Client;

class EndpointControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/endpoints';

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
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user1']);
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

    public function testCreateEndpointWithEmptyType()
    {
        $this->loadFixtures([
            LoadEndpointData::class,
        ]);
        $client = $this->client;
        $service = $this->getMockBuilder(SnsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPlatformEndpoint'])
            ->getMock();
        $service->expects($this->never())
            ->method('createPlatformEndpoint');
        $client->getContainer()->set('aws_sns.client', $service);
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user3']);
        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid endpoint type.', $data['message']);
    }

    public function testCreateEndpointWithEmptyToken()
    {
        $this->loadFixtures([
            LoadEndpointData::class,
        ]);
        $params = [
            'type' => 'android',
            'token' => '',
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(SnsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPlatformEndpoint'])
            ->getMock();
        $service->expects($this->never())
            ->method('createPlatformEndpoint');
        $client->getContainer()->set('aws_sns.client', $service);
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user3'], json_encode($params));
        $response = $client->getResponse();
        $errors = ['token' => 'This value should not be blank.'];
        $this->assertResponseHasErrors($response, $errors);
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
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user3'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertEquals($params['token'], $data['token']);
        $this->assertEquals($params['type'], $data['type']);
    }

    public function testCreateDuplicatedEndpoints()
    {
        $this->loadFixtures([
            LoadEndpointData::class,
        ]);
        $params = [
            'type' => 'ios',
            'token' => '1ec5197cd9813708119e591e8793f75ba7049c160baa8861e9d554e69528246d',
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(SnsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPlatformEndpoint', 'deleteEndpoint'])
            ->getMock();
        $service->expects($this->once())
            ->method('createPlatformEndpoint')
            ->willReturn(['EndpointArn' => 'arn:zzz']);
        $service->expects($this->once())
            ->method('deleteEndpoint')
            ->with(['EndpointArn' => 'arn:aws:sns:us-east-1:863632456175:endpoint/APNS/powerline_ios_staging/cf70b808-444d-390c-bb5b-f05ebe8aacfb']);
        $client->getContainer()->set('aws.sns', $service);
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user3'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertEquals($params['token'], $data['token']);
        $this->assertEquals($params['type'], $data['type']);
    }
}
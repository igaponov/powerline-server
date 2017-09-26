<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadDeviceData;

class DeviceControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/devices';

    /**
     * @var Client
     */
    private $client;

    public static function setUpBeforeClass()
    {
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadDeviceData::class,
        ]);
    }

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

    public function testCreateDevice()
    {
        $faker = Factory::create();
        $params = [
            'id' => $faker->uuid,
            'identifier' => 'jHJBv1e97rBfK9xG1JVC4xB9h8XTPuv',
            'timezone' => $faker->randomDigit,
            'version' => $faker->lexify(),
            'os' => $faker->lexify(),
            'model' => $faker->lexify(),
            'type' => 'android',
        ];
        $client = $this->client;
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization' => 'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertSame($params['id'], $data['id']);
    }

    public function testCreateDeviceWithDuplicatedId()
    {
        $faker = Factory::create();
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $device = $repository->getReference('device_1');
        $params = [
            'id' => $device->getId(),
            'identifier' => 'jHJBv1e97rBfK9xG1JVC4xB9h8XTPuv',
            'timezone' => $faker->randomDigit,
            'version' => $faker->lexify(),
            'os' => $faker->lexify(),
            'model' => $faker->lexify(),
            'type' => 'android',
        ];
        $client = $this->client;
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization' => 'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, ['id' => 'This value is already used.']);
    }
}
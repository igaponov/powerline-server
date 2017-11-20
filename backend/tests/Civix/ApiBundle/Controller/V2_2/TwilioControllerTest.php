<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class TwilioControllerTest extends WebTestCase
{
    private const API_ENDPOINT = '/api/v2.2/twilio/token';

    /**
     * @var null|Client
     */
    private $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([LoadUserData::class]);
    }

    public function setUp(): void
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown(): void
    {
        $this->client = null;
        parent::tearDown();
    }

    public function testGetToken()
    {
        $faker = Factory::create();
        $client = $this->client;
        $params = [
            'endpoint_id' => $faker->word,
            'identity' => $faker->word,
        ];
        $client->request('GET', self::API_ENDPOINT, $params, [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['identity'], $data['identity']);
        $this->assertNotEmpty($data['token']);
    }

    /**
     * @param array $params
     * @dataProvider getParams
     */
    public function testGetTokenWithoutParameters(array $params)
    {
        $this->client->request('GET', self::API_ENDPOINT, $params, [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
    }

    public function getParams()
    {
        return [
            [['endpoint_id' => 'zzz']],
            [['identity' => 'xxx']],
        ];
    }
}

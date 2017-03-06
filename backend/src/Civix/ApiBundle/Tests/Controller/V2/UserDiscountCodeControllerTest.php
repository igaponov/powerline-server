<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDiscountCodeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserDiscountCodeControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/discount-code';

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testGetUserDiscountCode()
    {
        $repository = $this->loadFixtures([
            LoadDiscountCodeData::class,
        ])->getReferenceRepository();
        /** @var DiscountCode $code */
        $code = $repository->getReference('discount_code_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertEquals($code->getId(), $data['id']);
        $this->assertEquals($code->getCode(), $data['code']);
        $this->assertEquals($code->getCreatedAt()->format(DATE_ISO8601), $data['created_at']);
    }

    public function testUserDiscountCodeNotFound()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }
}
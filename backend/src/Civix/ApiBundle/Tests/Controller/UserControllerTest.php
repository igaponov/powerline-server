<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserControllerTest  extends WebTestCase
{
    const API_ENDPOINT = '/api/users/';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        // Creates a initial client
        $this->client = static::createClient();
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testGetUsersAction()
    {
        $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowerData::class,
        ]);

        $this->client->request('GET', self::API_ENDPOINT, ['q' => 'user'], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(8, $data);
    }

    public function testGetUnfollowingUsersAction()
    {
        $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowerData::class,
        ]);

        $this->client->request('GET', self::API_ENDPOINT, ['q' => 'r', 'unfollowing' => true], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(5, $data);
    }
}
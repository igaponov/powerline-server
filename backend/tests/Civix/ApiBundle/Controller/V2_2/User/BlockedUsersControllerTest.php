<?php

namespace Tests\Civix\ApiBundle\Controller\V2_2\User;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadBlockedUserData;

class BlockedUsersControllerTest extends WebTestCase
{
    private const API_ENDPOINT = '/api/v2.2/user/blocked-users';

    /**
     * @var null|Client
     */
    private $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadBlockedUserData::class,
        ]);
    }

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown()
    {
        $this->client = null;
        parent::tearDown();
    }

    /**
     * @QueryCount("2")
     */
    public function testGetBlockedUsers()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User[] $users */
        $users = [
            $repository->getReference('user_2'),
            $repository->getReference('user_4'),
        ];
        $this->client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        foreach ($data as $k => $item) {
            $this->assertCount(6, $item);
            $this->assertSame($users[$k]->getId(), $item['id']);
            $this->assertSame($users[$k]->getUsername(), $item['username']);
            $this->assertSame($users[$k]->getFirstName(), $item['first_name']);
            $this->assertSame($users[$k]->getLastName(), $item['last_name']);
            $this->assertSame($users[$k]->getEmail(), $item['email']);
            $this->assertContains($users[$k]->getAvatarFileName(), $item['avatar_file_name']);
        }
    }

    /**
     * @QueryCount("5")
     */
    public function testUnblockUser()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_4');
        $this->client->request('DELETE', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $this->client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertCount(0, $user->getBlockedBy());
    }

    /**
     * @QueryCount("2")
     */
    public function testUnblockNotBlockedUserReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_3');
        $this->client->request('DELETE', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User is not blocked.', $data['message']);
    }

    /**
     * @QueryCount("5")
     */
    public function testBlockUser()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_3');
        $this->client->request('PUT', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $user->getBlockedBy());
        $this->assertCount(4, $data);
        $this->assertSame($user->getId(), $data['id']);
        $this->assertSame($user->getUsername(), $data['username']);
        $this->assertSame($user->getFirstName(), $data['first_name']);
        $this->assertSame($user->getLastName(), $data['last_name']);
    }

    /**
     * @QueryCount("2")
     */
    public function testBlockBlockedUserReturnsError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $this->client->request('PUT', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User is blocked already.', $data['message']);
    }
}

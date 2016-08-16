<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\DBAL\Connection;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPetitionsControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/user-petitions';

    /**
     * @var null|Client
     */
    private $client = null;

    /**
     * @var ReferenceRepository
     */
    private $repository;

    public function setUp()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown()
    {
        $this->client = null;
        $this->repository = null;
        parent::tearDown();
    }

    public function testGetUserPetitions()
    {
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['payload']);
    }

    public function testGetUserPetitionsEmpty()
    {
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }

    public function testSubscribeToUserPetition()
    {
        $petition = $this->repository->getReference('user_petition_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM petition_subscribers WHERE userpetition_id = ?',
            [$petition->getId()]
        );
        $this->assertEquals(1, $count);
    }

    public function testSubscribeToUserPetitionAccessDenied()
    {
        $petition = $this->repository->getReference('user_petition_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUnsubscribeFromUserPetition()
    {
        $client = $this->client;
        $petition = $this->repository->getReference('user_petition_1');
        $user = $this->repository->getReference('user_2');
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $conn->insert(
            'petition_subscribers',
            ['user_id' => $user->getId(), 'userpetition_id' => $petition->getId()]
        );
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM petition_subscribers WHERE userpetition_id = ?',
            [$petition->getId()]
        );
        $this->assertEquals(0, $count);
    }

    public function testUnsubscribeFromNonSubscribedUserPetition()
    {
        $client = $this->client;
        $petition = $this->repository->getReference('user_petition_1');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }
}
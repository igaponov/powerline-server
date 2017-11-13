<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPostControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/posts';

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
            LoadPostData::class,
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

    public function testGetPosts()
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

    public function testGetPostsEmpty()
    {
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }

    public function testSubscribeToPost()
    {
        $post = $this->repository->getReference('post_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$post->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM post_subscribers WHERE post_id = ?',
            [$post->getId()]
        );
        $this->assertEquals(1, $count);
    }

    public function testSubscribeToPostAccessDenied()
    {
        $petition = $this->repository->getReference('post_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUnsubscribeFromPost()
    {
        $client = $this->client;
        $petition = $this->repository->getReference('post_1');
        $user = $this->repository->getReference('user_2');
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $conn->insert(
            'post_subscribers',
            ['user_id' => $user->getId(), 'post_id' => $petition->getId()]
        );
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM post_subscribers WHERE post_id = ?',
            [$petition->getId()]
        );
        $this->assertEquals(0, $count);
    }

    public function testUnsubscribeFromNonSubscribedPost()
    {
        $client = $this->client;
        $petition = $this->repository->getReference('post_1');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }
}
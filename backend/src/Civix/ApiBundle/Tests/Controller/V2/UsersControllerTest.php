<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class UsersControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/users';

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

    /**
     * @QueryCount(3)
     */
    public function testGetNotFollowingUserProfileIsOk()
    {
        $repository = $this->loadFixtures([
            LoadKarmaData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $client = $this->client;
        /** @var EntityManagerInterface $em */
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('city', $data);
        $this->assertArrayNotHasKey('state', $data);
        $this->assertArrayHasKey('karma', $data);
        $this->assertEquals(63, $data['karma']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getBio(), $data['bio']);
    }

    /**
     * @QueryCount(3)
     */
    public function testGetFollowingUserProfileIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadKarmaData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('state', $data);
        $this->assertArrayHasKey('karma', $data);
        $this->assertEquals(0, $data['karma']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getBio(), $data['bio']);
    }
}
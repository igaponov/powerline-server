<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserFollowerControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/followers';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        // Creates a initial client
        $this->client = NULL;
    }

    public function testGetFollowings()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertArrayHasKey('status', $item);
            $this->assertThat(
                $item['user']['id'],
                $this->logicalOr(
                    $repository->getReference('userfollowtest1')->getId(),
                    $repository->getReference('userfollowtest2')->getId(),
                    $repository->getReference('userfollowtest3')->getId()
                )
            );
        }
    }

    public function testGetFollowers()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
        $this->assertArrayHasKey('status', $data['payload'][0]);
        $this->assertSame(
            $data['payload'][0]['follower']['id'],
            $repository->getReference('followertest')->getId()
        );

    }

    public function testFollowUserIsSuccessful()
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        $user = $repository->getReference('userfollowtest1');
        $follower = $repository->getReference('followertest');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEmpty($response->getContent());
        /** @var UserFollow[] $userFollow */
        $userFollow = $this->em->getRepository(UserFollow::class)
            ->findBy(['user' => $user]);
        $this->assertCount(1, $userFollow);
        $this->assertSame($follower->getId(), $userFollow[0]->getFollower()->getId());
        $this->assertSame(UserFollow::STATUS_PENDING, $userFollow[0]->getStatus());
    }

    public function testApproveFollowUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var UserFollow $userFollow */
        $userFollow = $repository->getReference('userfollowtest2_followertest');
        $user = $userFollow->getUser();
        $follower = $userFollow->getFollower();
        $client = $this->client;
        $client->request('PATCH', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->em->refresh($userFollow);
        $this->assertSame($follower->getId(), $userFollow->getFollower()->getId());
        $this->assertSame(UserFollow::STATUS_ACTIVE, $userFollow->getStatus());
    }

    public function testUnfollowActiveUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('userfollowtest1');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $followers = $this->em->getRepository(UserFollow::class)->findBy(['user' => $user]);
        $this->assertCount(0, $followers);
    }

    public function testUnfollowPendingUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('userfollowtest2');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $followers = $this->em->getRepository(UserFollow::class)->findBy(['user' => $user]);
        $this->assertCount(0, $followers);
    }
}
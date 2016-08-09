<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
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
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->client = NULL;
        $this->em = null;
        parent::tearDown();
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
            $data['payload'][0]['id'],
            $repository->getReference('followertest')->getId()
        );
    }

    public function testGetFollower()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var User $follower */
        $follower = $repository->getReference('followertest');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(19, $data);
        $this->assertEquals('active', $data['status']);
        $this->assertEquals($follower->getId(), $data['id']);
        $this->assertEquals($follower->getType(), $data['type']);
        $this->assertEquals($follower->getUsername(), $data['username']);
        $this->assertEquals($follower->getFirstName(), $data['first_name']);
        $this->assertEquals($follower->getLastName(), $data['last_name']);
        $this->assertEquals($follower->getFullName(), $data['full_name']);
        $this->assertContains(User::DEFAULT_AVATAR, $data['avatar_file_name']);
        $this->assertEquals($follower->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($follower->getCity(), $data['city']);
        $this->assertEquals($follower->getState(), $data['state']);
        $this->assertEquals($follower->getCountry(), $data['country']);
        $this->assertEquals($follower->getFacebookLink(), $data['facebook_link']);
        $this->assertEquals($follower->getTwitterLink(), $data['twitter_link']);
        $this->assertEquals($follower->getBio(), $data['bio']);
        $this->assertEquals($follower->getSlogan(), $data['slogan']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    public function testGetPendingFollower()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var User $follower */
        $follower = $repository->getReference('followertest');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(14, $data);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals($follower->getId(), $data['id']);
        $this->assertEquals($follower->getFullName(), $data['full_name']);
        $this->assertEquals($follower->getUsername(), $data['username']);
        $this->assertEquals($follower->getFirstName(), $data['first_name']);
        $this->assertEquals($follower->getLastName(), $data['last_name']);
        $this->assertContains(User::DEFAULT_AVATAR, $data['avatar_file_name']);
        $this->assertEquals($follower->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($follower->getCountry(), $data['country']);
        $this->assertEquals($follower->getBio(), $data['bio']);
        $this->assertEquals($follower->getSlogan(), $data['slogan']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    public function testApproveFollowUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var UserFollow $userFollow */
        $userFollow = $repository->getReference('userfollowtest2_followertest');
        $follower = $userFollow->getFollower();
        $client = $this->client;
        $client->request('PATCH', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->em->refresh($userFollow);
        $this->assertSame($follower->getId(), $userFollow->getFollower()->getId());
        $this->assertSame(UserFollow::STATUS_ACTIVE, $userFollow->getStatus());
    }

    public function testUnapproveActiveUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var UserFollow $userFollow */
        $userFollow = $repository->getReference('userfollowtest1_followertest');
        $user = $userFollow->getUser();
        $follower = $userFollow->getFollower();
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $followers = $this->em->getRepository(UserFollow::class)->findBy(['user' => $user]);
        $this->assertCount(0, $followers);
    }

    public function testUnapprovePendingUserIsSuccessful()
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
        $client->request('DELETE', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $followers = $this->em->getRepository(UserFollow::class)->findBy(['user' => $user]);
        $this->assertCount(0, $followers);
    }
}
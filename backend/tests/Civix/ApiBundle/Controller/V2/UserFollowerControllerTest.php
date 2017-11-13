<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\DataCollector\RabbitMQDataCollector;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Issue\PM510;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
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
    private $client;

    public function setUp(): void
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown(): void
    {
        $this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

    public function testGetFollowers(): void
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

    public function testGetFollowersSorting(): void
    {
        $repository = $this->loadFixtures([
            PM510::class,
        ])->getReferenceRepository();
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
        $this->assertSame($user4->getId(), $data['payload'][0]['id']);
        $this->assertSame($user3->getId(), $data['payload'][1]['id']);
    }

    public function testGetFollower(): void
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
        $this->assertCount(22, $data);
        $this->assertEquals('active', $data['status']);
        $this->assertEquals($follower->getId(), $data['id']);
        $this->assertEquals($follower->getType(), $data['type']);
        $this->assertEquals($follower->getUsername(), $data['username']);
        $this->assertEquals($follower->getFirstName(), $data['first_name']);
        $this->assertEquals($follower->getLastName(), $data['last_name']);
        $this->assertEquals($follower->getFullName(), $data['full_name']);
        $this->assertEquals($follower->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($follower->getCity(), $data['city']);
        $this->assertEquals($follower->getState(), $data['state']);
        $this->assertEquals($follower->getCountry(), $data['country']);
        $this->assertEquals($follower->getFacebookLink(), $data['facebook_link']);
        $this->assertEquals($follower->getTwitterLink(), $data['twitter_link']);
        $this->assertEquals($follower->getBio(), $data['bio']);
        $this->assertEquals($follower->getSlogan(), $data['slogan']);
        $this->assertEquals($follower->getInterests(), $data['interests']);
        $this->assertTrue($data['notifying']);
        $this->assertNotEmpty($data['do_not_disturb_till']);
        $this->assertContains($follower->getAvatarFileName(), $data['avatar_file_name']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    public function testGetPendingFollower(): void
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
        $this->assertCount(17, $data);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals($follower->getId(), $data['id']);
        $this->assertEquals($follower->getFullName(), $data['full_name']);
        $this->assertEquals($follower->getUsername(), $data['username']);
        $this->assertEquals($follower->getFirstName(), $data['first_name']);
        $this->assertEquals($follower->getLastName(), $data['last_name']);
        $this->assertEquals($follower->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($follower->getCountry(), $data['country']);
        $this->assertEquals($follower->getBio(), $data['bio']);
        $this->assertEquals($follower->getSlogan(), $data['slogan']);
        $this->assertEquals($follower->getInterests(), $data['interests']);
        $this->assertTrue($data['notifying']);
        $this->assertNotEmpty($data['do_not_disturb_till']);
        $this->assertContains($follower->getAvatarFileName(), $data['avatar_file_name']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    /**
     * @QueryCount(11)
     */
    public function testApproveFollowUserIsSuccessful(): void
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var UserFollow $userFollow */
        $userFollow = $repository->getReference('userfollowtest2_followertest');
        $follower = $userFollow->getFollower();
        $client = $this->client;
        $client->enableProfiler();
        $client->request('PATCH', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->em->refresh($userFollow);
        $this->assertSame($follower->getId(), $userFollow->getFollower()->getId());
        $this->assertSame(UserFollow::STATUS_ACTIVE, $userFollow->getStatus());
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(UserEvents::FOLLOW_REQUEST_APPROVE, $msg->getEventName());
        $this->assertInstanceOf(UserFollowEvent::class, $msg->getEvent());
    }

    /**
     * @QueryCount(8)
     */
    public function testApproveSecondFollowUserIsSuccessful(): void
    {
        $repository = $this->loadFixtures([
            LoadKarmaData::class,
            LoadUserFollowerData::class,
        ])->getReferenceRepository();
        /** @var UserFollow $userFollow */
        $userFollow = $repository->getReference('user_3_user_1');
        $follower = $userFollow->getFollower();
        $client = $this->client;
        $client->enableProfiler();
        $client->request('PATCH', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->em->refresh($userFollow);
        $this->assertSame($follower->getId(), $userFollow->getFollower()->getId());
        $this->assertSame(UserFollow::STATUS_ACTIVE, $userFollow->getStatus());
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(UserEvents::FOLLOW_REQUEST_APPROVE, $msg->getEventName());
        $this->assertInstanceOf(UserFollowEvent::class, $msg->getEvent());
    }

    /**
     * @QueryCount(7)
     */
    public function testUnapproveActiveUserIsSuccessful(): void
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
        $client->enableProfiler();
        $client->request('DELETE', self::API_ENDPOINT.'/'.$follower->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $followers = $this->em->getRepository(UserFollow::class)->findBy(['user' => $user]);
        $this->assertCount(0, $followers);
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(UserEvents::UNFOLLOW, $msg->getEventName());
        $this->assertInstanceOf(UserFollowEvent::class, $msg->getEvent());
    }

    public function testUnapprovePendingUserIsSuccessful(): void
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
<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserFollowingControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/followings';

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
                $item['id'],
                $this->logicalOr(
                    $repository->getReference('userfollowtest1')->getId(),
                    $repository->getReference('userfollowtest2')->getId(),
                    $repository->getReference('userfollowtest3')->getId()
                )
            );
        }
    }

    public function testGetFollowed()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('userfollowtest1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(22, $data);
        $this->assertEquals('active', $data['status']);
        $this->assertEquals($user->getId(), $data['id']);
        $this->assertEquals($user->getType(), $data['type']);
        $this->assertEquals($user->getUsername(), $data['username']);
        $this->assertEquals($user->getFirstName(), $data['first_name']);
        $this->assertEquals($user->getLastName(), $data['last_name']);
        $this->assertEquals($user->getFullName(), $data['full_name']);
        $this->assertEquals($user->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($user->getCity(), $data['city']);
        $this->assertEquals($user->getState(), $data['state']);
        $this->assertEquals($user->getCountry(), $data['country']);
        $this->assertEquals($user->getFacebookLink(), $data['facebook_link']);
        $this->assertEquals($user->getTwitterLink(), $data['twitter_link']);
        $this->assertEquals($user->getBio(), $data['bio']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getInterests(), $data['interests']);
        $this->assertTrue($data['notifying']);
        $this->assertNotEmpty($data['do_not_disturb_till']);
        $this->assertContains($user->getAvatarFileName(), $data['avatar_file_name']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    public function testGetPendingFollowed()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('userfollowtest2');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(17, $data);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals($user->getId(), $data['id']);
        $this->assertEquals($user->getFullName(), $data['full_name']);
        $this->assertEquals($user->getUsername(), $data['username']);
        $this->assertEquals($user->getFirstName(), $data['first_name']);
        $this->assertEquals($user->getLastName(), $data['last_name']);
        $this->assertEquals($user->getBirth(), new \DateTime($data['birth']));
        $this->assertEquals($user->getCountry(), $data['country']);
        $this->assertEquals($user->getBio(), $data['bio']);
        $this->assertEquals($user->getSlogan(), $data['slogan']);
        $this->assertEquals($user->getInterests(), $data['interests']);
        $this->assertTrue($data['notifying']);
        $this->assertNotEmpty($data['do_not_disturb_till']);
        $this->assertContains($user->getAvatarFileName(), $data['avatar_file_name']);
        $this->assertArrayHasKey('date_create', $data);
        $this->assertArrayHasKey('date_approval', $data);
    }

    public function testFollowUserIsSuccessful()
    {
        $repository = $this->loadFixtures([LoadUserData::class])->getReferenceRepository();
        /** @var UserFollow $userFollow */
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
        $tester = new SocialActivityTester($client->getContainer()->get('doctrine')->getManager());
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_FOLLOW_REQUEST, $user->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
        $result = $client->getContainer()->get('doctrine.dbal.default_connection')
            ->fetchAssoc('SELECT * FROM karma');
        $this->assertArraySubset([
            'user_id' => $userFollow[0]->getFollower()->getId(),
            'type' => Karma::TYPE_FOLLOW,
            'points' => 10,
            'metadata' => serialize([
                'following_id' => $userFollow[0]->getUser()->getId(),
            ]),
        ], $result);
    }

    public function testFollowSecondUserIsSuccessful()
    {
        $repository = $this->loadFixtures([
            LoadKarmaData::class
        ])->getReferenceRepository();
        /** @var UserFollow $userFollow */
        $user = $repository->getReference('user_2');
        $follower = $repository->getReference('user_1');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEmpty($response->getContent());
        /** @var UserFollow[] $userFollow */
        $userFollow = $this->em->getRepository(UserFollow::class)
            ->findBy(['user' => $user]);
        $this->assertCount(1, $userFollow);
        $this->assertSame($follower->getId(), $userFollow[0]->getFollower()->getId());
        $this->assertSame(UserFollow::STATUS_PENDING, $userFollow[0]->getStatus());
        $results = $client->getContainer()->get('doctrine.dbal.default_connection')
            ->fetchColumn(
                'SELECT COUNT(*) FROM karma WHERE user_id = ? AND type = ?',
                [$follower->getId(), Karma::TYPE_FOLLOW]
            );
        $this->assertEquals(1, $results);
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
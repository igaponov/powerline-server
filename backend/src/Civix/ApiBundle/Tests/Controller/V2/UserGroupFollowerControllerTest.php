<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserGroupFollowerControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/group-followers';

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

    public function testFollowGroupWithoutUsers()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group');
        $user = $repository->getReference('followertest');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertSame(204, $response->getStatusCode(), $response->getContent());
        /** @var UserFollow[] $userFollow1 */
        $userFollows = $this->em->getRepository(UserFollow::class)
            ->findBy(array('follower' => $user));
        $this->assertCount(0, $userFollows);
    }

    public function testFollowSecretGroup()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('testfollowsecretgroups');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testFollowPrivateGroup()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('testfollowprivategroups');
        $user = $repository->getReference('followertest');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertSame(204, $response->getStatusCode(), $response->getContent());
        $userFollows = $this->em->getRepository(UserFollow::class)
            ->findBy(array('follower' => $user));
        $this->assertCount(4, $userFollows);
    }

    public function testFollowGroupWithFollowedByAnotherUserMembers()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class,
            LoadUserFollowData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('testfollowprivategroups');
        $user = $repository->getReference('userfollowtest1');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
        $response = $client->getResponse();
        $this->assertSame(204, $response->getStatusCode(), $response->getContent());
        $userFollows = $this->em->getRepository(UserFollow::class)
            ->findBy(array('follower' => $user));
        $this->assertCount(4, $userFollows);
    }
}
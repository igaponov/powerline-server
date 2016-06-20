<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
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
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        // Creates a initial client
        $this->client = NULL;
    }

    public function testFollowGroupWithoutUsers()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupData::class,
            LoadUserGroupData::class,
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
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('testfollowsecretgroups');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Test to follow private group
     *
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testFollowPrivateGroup()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupData::class,
            LoadUserGroupData::class,
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
}
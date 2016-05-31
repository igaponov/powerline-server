<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Client;

class UserGroupControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/groups';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Client
     */
    private $client = null;

    /**
     * @var ProxyReferenceRepository
     */
    private $repository;

    public function setUp()
    {
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        // Creates a initial client
        $this->client = NULL;
    }

    public function testGetGroups()
    {
        $group1 = $this->repository->getReference('userfollowtest1_testfollowsecretgroups');
        $group2 = $this->repository->getReference('userfollowtest1_testfollowprivategroups');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertArrayHasKey('username', $item);
            $this->assertArrayHasKey('join_status', $item);
            $this->assertThat(
                $item['id'],
                $this->logicalOr($group1->getGroup()->getId(), $group2->getGroup()->getId())
            );
        }
    }

    public function testGetGroupsIsEmpty()
    {
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }
}
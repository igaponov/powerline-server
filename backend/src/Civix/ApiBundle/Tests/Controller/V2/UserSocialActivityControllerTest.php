<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSocialActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserSocialActivityControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/social-activities';

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
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class,
            LoadUserFollowData::class,
            LoadSocialActivityData::class,
        ])->getReferenceRepository();
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        $this->repository = null;
        parent::tearDown();
    }

    public function testGetOwnSocialActivitiesIsOk()
    {
        $ids = [];
        foreach ([1, 2, 3, 4, 9] as $key) {
            $ids[] = $this->repository->getReference('social_activity_'.$key)->getId();
        }
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(5, $data['totalItems']);
        $this->assertCount(5, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertContains($item['id'], $ids);
        }
    }

    public function testGetFollowingSocialActivitiesIsOk()
    {
        $ids = [];
        foreach ([6, 7, 8, 10] as $key) {
            $ids[] = $this->repository->getReference('social_activity_'.$key)->getId();
        }
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, ['following' => true], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(4, $data['totalItems']);
        $this->assertCount(4, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertContains($item['id'], $ids);
        }
    }
}
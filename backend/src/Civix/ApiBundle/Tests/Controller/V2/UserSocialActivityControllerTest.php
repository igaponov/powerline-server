<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSocialActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
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
            LoadUserGroupData::class,
            LoadUserFollowerData::class,
            LoadUserGroupOwnerData::class,
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

    /**
     * @param $params
     * @param $keys
     * @param $count
     * @dataProvider getTabs
     */
    public function testGetSocialActivitiesIsOk($params, $keys, $count)
    {
        $ids = [];
        foreach ($keys as $key) {
            $ids[] = $this->repository->getReference('social_activity_'.$key)->getId();
        }
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, $params, [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame($count, $data['totalItems']);
        $this->assertCount($count, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertContains($item['id'], $ids);
            if (isset($params['tab'])) {
                $this->assertSame($params['tab'], $item['tab']);
            } else {
                $this->assertNotEmpty($item['tab']);
            }
        }
    }

    public function getTabs()
    {
        return [
            'default' => [[], [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16], 16],
            'you' => [['tab' => 'you'], [1, 2, 3, 6, 8, 9, 10, 11, 12, 13, 14], 11],
            'following' => [['tab' => 'following'], [4, 5, 7, 15, 16], 5],
        ];
    }
}
<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadInviteData;
use Symfony\Bundle\FrameworkBundle\Client;

class UserInviteController extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/invites';

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

    public function testGetInvites()
    {
        $repository = $this->loadFixtures([
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group2 = $repository->getReference('group_2');
        $group3 = $repository->getReference('group_3');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertArrayHasKey('official_title', $item);
            $this->assertThat(
                $item['id'],
                $this->logicalOr($group3->getId(), $group2->getId())
            );
        }
    }}
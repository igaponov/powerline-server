<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Symfony\Bundle\FrameworkBundle\Client;

class SearchControllerTest  extends WebTestCase
{
    const API_ENDPOINT = '/api/search';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        // Creates a initial client
        $this->client = static::createClient();
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testSearch()
    {
        $this->loadFixtures([
            LoadUserData::class,
            LoadUserGroupOwnerData::class,
            LoadUserRepresentativeData::class,
        ]);

        $this->client->request('GET', self::API_ENDPOINT, ['query' => 'r'], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['groups']);
        $this->assertCount(2, $data['representatives']);
        $this->assertCount(8, $data['users']);
    }

    public function testEmptyQueryReturnsNothing()
    {
        $this->loadFixtures([
            LoadUserData::class,
            LoadUserGroupOwnerData::class,
            LoadUserRepresentativeData::class,
        ]);

        $this->client->request('GET', self::API_ENDPOINT, ['query' => ''], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data);
    }

    public function testSearchFriends()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user1 */
        $user1 = $repository->getReference('user_1');
        /** @var User $user2 */
        $user2 = $repository->getReference('user_2');
        /** @var User $user3 */
        $user3 = $repository->getReference('user_3');
        /** @var User $user4 */
        $user4 = $repository->getReference('user_4');
        $params = ['emails' => [
            $user1->getEmailHash(),
            $user2->getEmailHash(),
            $user4->getEmailHash(),
        ], 'phones' => [
            $user1->getPhoneHash(),
            $user3->getPhoneHash(),
            $user4->getPhoneHash(),
        ]];
        $this->client->request('GET', self::API_ENDPOINT.'/friends', $params, [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        foreach ($data as $item) {
            $this->assertThat(
                $item['id'],
                $this->logicalOr($user2->getId(), $user3->getId(), $user4->getId())
            );
        }
    }
}
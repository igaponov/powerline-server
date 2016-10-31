<?php

namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Client;

class GroupSectionControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/group-sections';

    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    /**
     * @param array $params
     * @param array $errors
     * @dataProvider getInvalidParams
     */
    public function testUpdateGroupSectionReturnsErrors($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $section = $repository->getReference('group_1_section_1');
        $client->request('PUT', self::API_ENDPOINT.'/'.$section->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupSectionCredentialsForUpdateRequest
     */
    public function testUpdateGroupSectionWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $section = $repository->getReference($reference);
        $client->request('PUT', self::API_ENDPOINT.'/'.$section->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupSectionCredentialsForUpdateRequest
     */
    public function testUpdateGroupSectionIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $section = $repository->getReference($reference);
        $params = ['title' => 'some-title'];
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$section->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($section->getId(), $data['id']);
        $this->assertSame($params['title'], $data['title']);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupSectionCredentialsForUpdateRequest
     */
    public function testDeleteGroupSectionWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $section = $repository->getReference($reference);
        $client->request('DELETE', self::API_ENDPOINT.'/'.$section->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupSectionCredentialsForUpdateRequest
     */
    public function testDeleteGroupSectionIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $field = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    public function testGetGroupSectionUsersWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
        ])->getReferenceRepository();
        $section = $repository->getReference('group_1_section_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$section->getId().'/users', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupSectionCredentialsForGetRequest
     */
    public function testGetGroupSectionUsersIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionUserData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $section = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$section->getId().'/users', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertArrayHasKey('username', $item);
        }
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupSectionCredentialsForUpdateRequest
     */
    public function testAddUserToGroupSectionWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('user_1');
        $client = $this->client;
        $section = $repository->getReference($reference);
        $client->request('PUT', self::API_ENDPOINT.'/'.$section->getId().'/users/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupSectionCredentialsForUpdateRequest
     */
    public function testAddUserToGroupSectionIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $section = $repository->getReference($reference);
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$section->getId().'/users/'.$user4->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM groupsection_user WHERE groupsection_id = ? AND user_id = ?', [$section->getId(), $user4->getId()]);
        $this->assertEquals(1, $count);
    }

    public function testAddExistentUserToGroupSectionIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionUserData::class,
        ])->getReferenceRepository();
        $section = $repository->getReference('group_1_section_1');
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$section->getId().'/users/'.$user4->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM groupsection_user WHERE groupsection_id = ? AND user_id = ?', [$section->getId(), $user4->getId()]);
        $this->assertEquals(1, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupSectionCredentialsForUpdateRequest
     */
    public function testRemoveUserFromGroupSectionWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionUserData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('user_4');
        $client = $this->client;
        $section = $repository->getReference($reference);
        $client->request('DELETE', self::API_ENDPOINT.'/'.$section->getId().'/users/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupSectionCredentialsForUpdateRequest
     */
    public function testRemoveUserFromGroupSectionIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionUserData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $section = $repository->getReference($reference);
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$section->getId().'/users/'.$user4->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM groupsection_user WHERE groupsection_id = ? AND user_id = ?', [$section->getId(), $user4->getId()]);
        $this->assertEquals(0, $count);
    }

    public function getInvalidParams()
    {
        return [
            'empty title' => [
                [],
                [
                    'title' => 'This value should not be blank.',
                ]
            ],
        ];
    }

    public function getInvalidGroupSectionCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_3_section_1'],
            'outlier' => ['user1', 'group_3_section_1'],
        ];
    }

    public function getValidGroupSectionCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user1', 'group_1_section_1'],
            'manager' => ['user2', 'group_1_section_1'],
        ];
    }

    public function getValidGroupSectionCredentialsForGetRequest()
    {
        return [
            'owner' => ['user1', 'group_1_section_1'],
            'manager' => ['user2', 'group_1_section_1'],
            'member' => ['user4', 'group_1_section_1'],
        ];
    }
}
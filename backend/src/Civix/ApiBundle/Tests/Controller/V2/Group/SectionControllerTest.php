<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFieldsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class SectionControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{group}/sections';

    /**
     * @var null|Client
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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForGetRequest
     */
    public function testGetGroupSectionsIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupSectionData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
        $this->assertEquals('group_1_section_1', $data['payload'][0]['title']);
        $this->assertEquals('group_1_section_2', $data['payload'][1]['title']);
    }

    public function testGetGroupSectionsWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForUpdateRequest
     */
    public function testCreateGroupSectionWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param array $params
     * @param array $errors
     * @dataProvider getInvalidParams
     */
    public function testCreateGroupSectionReturnsErrors($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForUpdateRequest
     */
    public function testCreateGroupFieldIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $params = ['title' => 'some-title'];
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['title'], $data['title']);
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

    public function getValidGroupCredentialsForGetRequest()
    {
        return [
            'owner' => ['user1', 'group_1'],
            'manager' => ['user2', 'group_1'],
            'member' => ['user4', 'group_1'],
        ];
    }

    public function getInvalidGroupCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_1'],
            'outlier' => ['user4', 'group_2'],
        ];
    }

    public function getValidGroupCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user1', 'group_1'],
            'manager' => ['user2', 'group_1'],
        ];
    }
}
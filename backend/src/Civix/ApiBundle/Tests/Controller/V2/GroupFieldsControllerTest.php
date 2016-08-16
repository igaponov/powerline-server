<?php

namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFieldsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class GroupFieldsControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/group-fields';

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
    public function testUpdateGroupFieldReturnsErrors($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $field = $repository->getReference('test-group-field');
        $client->request('PUT', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $children = $data['errors']['children'];
        foreach ($errors as $child => $error) {
            $this->assertEquals([$error], $children[$child]['errors']);
        }
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidFieldCredentialsForUpdateRequest
     */
    public function testUpdateGroupFieldWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $field = $repository->getReference($reference);
        $client->request('PUT', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $params
     * @dataProvider getValidParams
     */
    public function testUpdateGroupFieldIsOk($params)
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $field = $repository->getReference('test-group-field');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($field->getId(), $data['id']);
        $this->assertSame($params['field_name'], $data['field_name']);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidFieldCredentialsForUpdateRequest
     */
    public function testDeleteGroupFieldWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $field = $repository->getReference($reference);
        $client->request('DELETE', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidFieldCredentialsForUpdateRequest
     */
    public function testDeleteGroupFieldIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $field = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$field->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    public function getInvalidParams()
    {
        return [
            'empty name' => [
                [],
                [
                    'field_name' => 'This value should not be blank.',
                ]
            ],
        ];
    }

    public function getValidParams()
    {
        return [
            'with name' => [
                [
                    'field_name' => 'some-name',
                ]
            ],
        ];
    }

    public function getInvalidFieldCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'another-group-field'],
            'outlier' => ['user1', 'another-group-field'],
        ];
    }

    public function getValidFieldCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user1', 'test-group-field'],
            'manager' => ['user2', 'test-group-field'],
        ];
    }
}
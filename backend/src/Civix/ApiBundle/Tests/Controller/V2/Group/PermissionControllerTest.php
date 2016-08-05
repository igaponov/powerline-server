<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class PermissionControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/permissions';

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

	public function testGetPermissionsNotFound()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(404, $response->getStatusCode(), $response->getContent());
	}

	public function testGetPermissionsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('user_1');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertEquals(true, $data['permissions_name']);
		$this->assertEquals(false, $data['permissions_contacts']);
		$this->assertEquals(false, $data['permissions_address']);
		$this->assertEquals(true, $data['permissions_city']);
		$this->assertEquals(false, $data['permissions_state']);
		$this->assertEquals(false, $data['permissions_country']);
		$this->assertEquals(false, $data['permissions_zip_code']);
		$this->assertEquals(false, $data['permissions_email']);
		$this->assertEquals(true, $data['permissions_phone']);
		$this->assertEquals(false, $data['permissions_responses']);
        $this->assertNotEmpty($data['permissions_approved_at']);
	}

	public function testUpdatePermissionsNotFound()
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(404, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdatePermissionSettingsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$client = $this->client;
		$params = [
            'permissions_name' => false,
            'permissions_contacts' => true,
            'permissions_address' => true,
            'permissions_city' => false,
            'permissions_state' => true,
            'permissions_country' => true,
            'permissions_zip_code' => true,
            'permissions_email' => true,
            'permissions_phone' => false,
            'permissions_responses' => true,
		];
        // owner
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"'], json_encode($params));
		$response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(false, $data['permissions_name']);
        $this->assertEquals(true, $data['permissions_contacts']);
        $this->assertEquals(true, $data['permissions_address']);
        $this->assertEquals(false, $data['permissions_city']);
        $this->assertEquals(true, $data['permissions_state']);
        $this->assertEquals(true, $data['permissions_country']);
        $this->assertEquals(true, $data['permissions_zip_code']);
        $this->assertEquals(true, $data['permissions_email']);
        $this->assertEquals(false, $data['permissions_phone']);
        $this->assertEquals(true, $data['permissions_responses']);
	}
}
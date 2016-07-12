<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Service\SocialActivityManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class PermissionSettingsControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/permission-settings';

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
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetPermissionSettingsWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForGetRequest
     */
	public function testGetPermissionSettingsIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
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
		$this->assertArrayHasKey('required_permissions', $data);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForUpdateRequest
     */
	public function testUpdatePermissionSettingsWithWrongCredentialsThrowsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdatePermissionSettingsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
		$client = $this->client;
		$manager = $this->getMockBuilder(SocialActivityManager::class)
			->disableOriginalConstructor()
			->getMock();
		$manager->expects($this->once())->method('noticeGroupsPermissionsChanged');
		$client->getContainer()->set('civix_core.social_activity_manager', $manager);
		$params = [
			'required_permissions' => [
				'permissions_name',
				'permissions_address',
				'permissions_city',
				'permissions_state',
				'permissions_country',
				'permissions_zip_code',
				'permissions_email',
				'permissions_phone',
				'permissions_responses',
			],
		];
        // owner
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['required_permissions'], $data['required_permissions']);
		$this->assertArrayHasKey('permissions_changed_at', $data);
        // manager
		$params['required_permissions'] = array_slice($params['required_permissions'], 2, 4);
		$client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['required_permissions'], $data['required_permissions']);
		$this->assertArrayHasKey('permissions_changed_at', $data);
	}

    public function getInvalidGroupCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user1', 'group_3'],
        ];
    }

    public function getValidGroupCredentialsForGetRequest()
    {
        return [
            'owner' => ['user3', 'group_3'],
            'manager' => ['user2', 'group_3'],
            'member' => ['user4', 'group_3'],
        ];
    }
}
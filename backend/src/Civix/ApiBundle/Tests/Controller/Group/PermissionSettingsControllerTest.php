<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\SocialActivityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class PermissionSettingsControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/group/permission-settings';

	/**
	 * @var null|Client
	 */
	private $client = null;

	public function setUp()
	{
		$this->loadFixtures([
			LoadGroupData::class,
		]);
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetPermissionSettingsIsOk()
	{
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('required_permissions', $data);
	}

	public function testUpdatePermissionSettingsIsOk()
	{
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
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['required_permissions'], $data['required_permissions']);
		$this->assertArrayHasKey('permissions_changed_at', $data);

		$params['required_permissions'] = array_slice($params['required_permissions'], 2, 4);
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['required_permissions'], $data['required_permissions']);
		$this->assertArrayHasKey('permissions_changed_at', $data);
	}
}
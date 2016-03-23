<?php
namespace Civix\ApiBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\DataFixtures\ORM\LoadSuperuserData;

class SecureControllerTest extends WebTestCase
{
	const API_LOGIN_ENDPOINT = '/api/secure/login';
	
	private $client = null;
	
	public function setUp()
	{
		// Creates a initial client
		$this->client = static::createClient();
		
		/** @var AbstractExecutor $fixtures */
		$fixtures = $this->loadFixtures([
				LoadUserData::class,
				LoadGroupData::class,
				LoadUserGroupData::class,
				LoadSuperuserData::class
		]);
		$reference = $fixtures->getReferenceRepository();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}
	
	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
	 */
	private function getManagerMock()
	{
		$manager = $this->getMockBuilder(EntityManager::class)
		->disableOriginalConstructor()
		->getMock();
	
		return $manager;
	}
	
	/**
	 * @group api
	 */
	public function testUserLoginWithoutCredentials()
	{
		$this->client->request('POST', self::API_LOGIN_ENDPOINT);

		$this->assertEquals(
				401,
				$this->client->getResponse()->getStatusCode(),
				'Should be not authorized'
				);
		
		$this->assertNotEmpty($this->client->getResponse()->headers->get('Access-Control-Allow-Origin'),
				'Should return cors headers');
	}
	
	/**
	 * @group api
	 */
	public function testSuperUserLoginWithCredentials()
	{
		$parameters = ['username' => 'admin', 'password' => 'admin'];
		$content = ['application/x-www-form-urlencoded'];
		
		$this->client->request('POST', self::API_LOGIN_ENDPOINT, $parameters, [], [], $content);
		
		$request_content = $this->client->getResponse()->getContent();
		
		$this->assertEquals(
				200,
				$this->client->getResponse()->getStatusCode(),
				'Should be superuser successfully authorized'
				);
	
		$this->assertNotEmpty($this->client->getResponse()->headers->get('Access-Control-Allow-Origin'),
				'Should return cors headers');
		
		$data = json_decode($request_content);
		
		$this->assertEquals(TRUE, isset($data->token) && !empty($data->token), 'Request result should contain a token and must be not empty');
	}
	
	/**
	 * @group api
	 */
	public function testUserLoginWithCredentials()
	{
		$parameters = ['username' => 'mobile1', 'password' => 'mobile1'];
		$content = ['application/x-www-form-urlencoded'];
	
		$this->client->request('POST', self::API_LOGIN_ENDPOINT, $parameters, [], [], $content);
	
		$request_content = $this->client->getResponse()->getContent();
	
		$this->assertEquals(
				200,
				$this->client->getResponse()->getStatusCode(),
				'Should be superuser successfully authorized'
				);
	
		$this->assertNotEmpty($this->client->getResponse()->headers->get('Access-Control-Allow-Origin'),
				'Should return cors headers');
	
		$data = json_decode($request_content);
	
		$this->assertEquals(TRUE, isset($data->token) && !empty($data->token), 'Request result should contain a token and must be not empty');
	}
	
	/**
	 * @group api
	 */
	public function testGroupLoginWithCredentials()
	{
		$parameters = ['username' => LoadGroupData::GROUP_NAME, 'password' => LoadGroupData::GROUP_PASSWORD];
		$content = ['application/x-www-form-urlencoded'];
	
		$this->client->request('POST', self::API_LOGIN_ENDPOINT, $parameters, [], [], $content);
	
		$request_content = $this->client->getResponse()->getContent();
	
		$this->assertEquals(
				200,
				$this->client->getResponse()->getStatusCode(),
				'Should be superuser successfully authorized'
				);
	
		$this->assertNotEmpty($this->client->getResponse()->headers->get('Access-Control-Allow-Origin'),
				'Should return cors headers');
	
		$data = json_decode($request_content);
	
		$this->assertEquals(TRUE, isset($data->token) && !empty($data->token), 'Request result should contain a token and must be not empty');
	}

}
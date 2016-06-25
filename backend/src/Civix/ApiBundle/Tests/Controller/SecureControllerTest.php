<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Symfony\Bundle\FrameworkBundle\Client;

class SecureControllerTest extends WebTestCase
{
	const API_LOGIN_ENDPOINT = '/api/secure/login';

	/**
	 * @var null|Client
	 */
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

		$data = json_decode($request_content);
	
		$this->assertEquals(TRUE, isset($data->token) && !empty($data->token), 'Request result should contain a token and must be not empty');
	}

	public function testRegistrationGroupWithWrongDataReturnsErrors()
	{
		$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$client->request('POST', '/api/secure/registration-group', [], [], []);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$errors = $data['errors']['children'];
		$this->assertContains('This value should not be blank.', $errors['username']['errors']);
		$this->assertContains('This value should not be blank.', $errors['plain_password']['errors']);
		$this->assertContains('This value should not be blank.', $errors['official_name']['errors']);
	}

	public function testRegistrationGroupIsOk()
	{
		$faker = Factory::create();
		$data = [
			'username' => $faker->userName,
			'plain_password' => $faker->password,
			'manager_first_name' => $faker->firstName,
			'manager_last_name' => $faker->lastName,
			'manager_email' => $faker->companyEmail,
			'manager_phone' => $faker->phoneNumber,
			'official_type' => $faker->randomElement(Group::getOfficialTypes()),
			'official_name' => $faker->company,
			'official_description' => $faker->text(),
			'official_address' => $faker->address,
			'official_city' => $faker->city,
			'official_state' => $faker->word,
			'acronym' => $faker->toUpper($faker->lexify()),
		];
		$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$client->request('POST', '/api/secure/registration-group', [], [], [], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());

		$group = json_decode($response->getContent(), true);
		$this->assertSame($data['username'], $group['username']);
		$this->assertSame($data['manager_first_name'], $group['manager_first_name']);
		$this->assertSame($data['manager_last_name'], $group['manager_last_name']);
		$this->assertSame($data['manager_email'], $group['manager_email']);
		$this->assertSame($data['manager_phone'], $group['manager_phone']);
		$this->assertSame($data['official_type'], $group['official_type']);
		$this->assertSame($data['official_name'], $group['official_title']);
		$this->assertSame($data['official_description'], $group['official_description']);
		$this->assertSame($data['official_address'], $group['official_address']);
		$this->assertSame($data['official_city'], $group['official_city']);
		$this->assertSame($data['official_state'], $group['official_state']);
		$this->assertSame($data['acronym'], $group['acronym']);

		$client->request('POST', '/api/secure/login', ['username' => $data['username'], 'password' => $data['plain_password']]);
		$response = $client->getResponse();
		$data = json_decode($response->getContent(), true);
		$this->assertNotEmpty($data['token']);
	}

	public function testRegistrationWithWrongDataReturnsErrors()
	{
		$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$client->request('POST', '/api/secure/registration', [], [], [], json_encode(['email' => 'qwerty']));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$errors = $data['errors'];
		$expectedErrors = [
			'username' => 'This value should not be blank.',
			'password' => 'This value should not be blank.',
			'firstName' => 'This value should not be blank.',
			'lastName' => 'This value should not be blank.',
			'zip' => 'This value should not be blank.',
			'email' => 'This value is not a valid email address.',
		];
		foreach ($errors as $error) {
			$this->assertEquals($expectedErrors[$error['property']], $error['message']);
		}
	}

	public function testRegistrationIsOk()
	{
		$faker = Factory::create();
		$data = [
			'username' => 'testUser1',
			'first_name' => $faker->firstName,
			'last_name' => $faker->lastName,
			'email' => 'reg.test+powerline@mail.com',
			'password' => $faker->password,
			'address1' => 'Bucklin',
			'address2' => $faker->address,
			'city' => 'Bucklin',
			'state' => 'KS',
			'zip' => '67834',
			'country' => 'US',
			'birth' => $faker->date(),
		];
		$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$client->request('POST', '/api/secure/registration', [], [], [], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());

		$result = json_decode($response->getContent(), true);
		$this->assertSame($data['username'], $result['username']);
		/** @var Connection $conn */
		$conn = $client->getContainer()->get('database_connection');
		$user = $conn->fetchAssoc('SELECT * FROM user WHERE username = ?', [$result['username']]);
		$this->assertSame($data['username'], $user['username']);
		$this->assertSame($data['first_name'], $user['firstName']);
		$this->assertSame($data['last_name'], $user['lastName']);
		$this->assertSame('regtest@mail.com', $user['email']);
		$this->assertSame($data['address1'], $user['address1']);
		$this->assertSame($data['address2'], $user['address2']);
		$this->assertSame($data['city'], $user['city']);
		$this->assertSame($data['state'], $user['state']);
		$this->assertSame($data['zip'], $user['zip']);
		$this->assertSame($data['country'], $user['country']);
		$this->assertSame(strtotime($data['birth']), strtotime($user['birth']));

		$client->request('POST', '/api/secure/login', ['username' => $data['username'], 'password' => $data['password']]);
		$response = $client->getResponse();
		$data = json_decode($response->getContent(), true);
		$this->assertNotEmpty($data['token']);
	}
}
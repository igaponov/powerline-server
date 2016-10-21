<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
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
				LoadGroupFollowerTestData::class,
				LoadUserGroupFollowerTestData::class,
				LoadSuperuserData::class
		]);
		$reference = $fixtures->getReferenceRepository();
	}

	public function tearDown()
	{
		$this->client = NULL;
        parent::tearDown();
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
		$parameters = ['username' => 'user1', 'password' => 'user1'];
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
		$parameters = ['username' => LoadGroupFollowerTestData::GROUP_NAME, 'password' => LoadGroupFollowerTestData::GROUP_PASSWORD];
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

	public function testFacebookRegistrationIsOk()
	{
		$faker = Factory::create();
		$data = [
		    'facebook_token' => 'xxx',
            'facebook_id' => 'yyy',
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
        $serviceId = 'civix_core.facebook_api';
        $mock = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['getFacebookId'])
            ->getMock();
        $mock->expects($this->any())->method('getFacebookId')->will($this->returnValue('yyy'));
        $client->getContainer()->set($serviceId, $mock);
		$client->request('POST', '/api/secure/registration-facebook', [], [], [], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());

		$result = json_decode($response->getContent(), true);
		$this->assertSame($data['username'], $result['username']);
		/** @var Connection $conn */
		$conn = $client->getContainer()->get('database_connection');
		$user = $conn->fetchAssoc('SELECT * FROM user WHERE username = ?', [$result['username']]);
		$this->assertSame($data['facebook_token'], $user['facebook_token']);
		$this->assertSame($data['facebook_id'], $user['facebook_id']);
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

        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->getContainer()->set($serviceId, $mock);
        $client->request('POST', '/api/secure/facebook/login', [
		    'facebook_token' => $data['facebook_token'],
            'facebook_id' => $data['facebook_id']
        ]);
		$response = $client->getResponse();
		$data = json_decode($response->getContent(), true);
		$this->assertNotEmpty($data['token']);
	}

    public function testForgotPassword()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $service = $this->getServiceMockBuilder('civix_core.email_sender')
            ->disableOriginalConstructor()
            ->setMethods(['sendResetPasswordEmail'])
            ->getMock();
        $service->expects($this->once())
            ->method('sendResetPasswordEmail')
            ->with(
                $user->getEmail(),
                $this->callback(function ($params) use ($user) {
                    $this->assertEquals($user->getOfficialName(), $params['name']);
                    $this->assertRegExp('=http://localhost/#/reset-password/[\w\d]{50}=', $params['link']);

                    return true;
                })
            );
        $client->getContainer()->set('civix_core.email_sender', $service);
        $client->request('POST', '/api/secure/forgot-password', ['email' => $user->getEmail()]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $data['status']);
	}

    public function testCheckResetToken()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $client->request('GET', '/api/secure/resettoken/'.$user->getResetPasswordToken());
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $data['status']);
	}

    public function testSaveNewPassword()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $client->request('POST', '/api/secure/resettoken/'.$user->getResetPasswordToken(), [], [], [], json_encode(['password' => 'new-pass', 'password_confirm' => 'new-pass']));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $data['status']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $data = $conn->fetchAssoc('SELECT password, reset_password_token, reset_password_at FROM user WHERE id = ?', [$user->getId()]);
        $this->assertNotEquals($user->getPassword(), $data['password']);
        $this->assertNull($data['reset_password_token']);
        $this->assertNull($data['reset_password_at']);
	}
}
<?php
namespace Tests\Civix\ApiBundle\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\DataCollector\RabbitMQDataCollector;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\FacebookApi;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDisabledUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class SecureControllerTest extends WebTestCase
{
	const API_LOGIN_ENDPOINT = '/api/secure/login';

	/**
	 * @var null|Client
	 */
	private $client;
	
	public function setUp(): void
    {
		// Creates a initial client
		$this->client = static::createClient();
	}

	public function tearDown(): void
    {
		$this->client = NULL;
        parent::tearDown();
    }

    /**
     * @QueryCount(1)
     */
    public function testUserLoginWithWrongCredentials(): void
    {
        $this->loadFixtures([LoadDisabledUserData::class]);
        $content = ['application/x-www-form-urlencoded'];
        foreach ($this->getCredentials() as $parameters) {
            $this->client->request('POST', self::API_LOGIN_ENDPOINT, $parameters, [], [], $content);
            $response = $this->client->getResponse();
            $this->assertEquals(401, $response->getStatusCode(), $response->getContent());
            $data = json_decode($response->getContent(), true);
            $this->assertSame('Authentication failed.', $data['message']);
        }
    }

    public function getCredentials(): array
    {
        return [
            'empty' => [[]],
            'invalid' => [['username' => 'user2', 'password' => 'user1']],
            'disabled' => [['username' => 'userD', 'password' => 'userD']]
        ];
    }

    /**
     * @QueryCount(1)
     */
	public function testUserLoginWithCredentials(): void
    {
        $this->loadFixtures([LoadUserData::class]);
		$parameters = ['username' => 'user1', 'password' => 'user1'];
		$content = ['application/x-www-form-urlencoded'];
		$this->client->request('POST', self::API_LOGIN_ENDPOINT, $parameters, [], [], $content);
		$response = $this->client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
	    $this->assertArrayHasKey('token', $data, 'Request result should contain a token');
		$this->assertNotEmpty($data['token'], 'Token must be not empty');
		$this->client->request('GET', '/api/v2/user', [], [], ['HTTP_TOKEN' => $data['token']]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @QueryCount(1)
     */
	public function testRegistrationWithDuplicateDataReturnsErrors(): void
    {
        $parameters = $this->getParameters();
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        foreach ($parameters as [$params, $expectedErrors]) {
            $client->request('POST', '/api/secure/registration', [], [], [], json_encode($params));
            $response = $client->getResponse();
            $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
            $data = json_decode($response->getContent(), true);
            /** @var array $errors */
            $errors = $data['errors'];
            $this->assertCount(count($expectedErrors), $errors, json_encode($errors));
            foreach ($errors as $error) {
                $this->assertEquals($expectedErrors[$error['property']], $error['message']);
            }
        }
	}

    public function getParameters(): array
    {
        $defaultParams = [
            'username' => 'userX',
            'email' => 'userX@example.com',
            'email_confirm' => 'userX@example.com',
            'password' => 'pass',
            'confirm' => 'pass',
            'first_name' => 'First',
            'last_name' => 'Last',
            'zip' => '12345',
        ];
        return [
            [
                array_replace($defaultParams, ['username' => 'user1']),
                ['email' => 'This value is already used.'],
            ],
            [
                array_replace($defaultParams, [
                    'email' => 'user1@example.com',
                    'email_confirm' => 'user1@example.com',
                ]),
                ['email' => 'This value is already used.'],
            ],
            [
                ['email' => 'qwerty'],
                [
                    'username' => 'This value should not be blank.',
                    'password' => 'This value should not be blank.',
                    'first_name' => 'This value should not be blank.',
                    'last_name' => 'This value should not be blank.',
                    'zip' => 'This value should not be blank.',
                    'email' => 'This value is not a valid email address.',
                    'email_confirm' => 'The email fields must match.',
                ],
            ]
        ];
	}

    /**
     * @QueryCount(4)
     */
	public function testRegistrationIsOk(): void
    {
        $this->loadFixtures([]);
		$faker = Factory::create();
        $path = __DIR__.'/../data/image.png';
        $password = $faker->password;
        $email = 'reg.test+powerline@mail.com';
        $params = [
			'username' => 'testUser1',
			'first_name' => $faker->firstName,
			'last_name' => $faker->lastName,
			'email' => $email,
			'email_confirm' => $email,
			'password' => $password,
			'confirm' => $password,
			'address1' => 'Bucklin',
			'address2' => $faker->address,
			'city' => 'Bucklin',
			'state' => 'KS',
			'zip' => '67834',
			'country' => 'US',
			'birth' => $faker->date(),
            'avatar_file_name' => $path,
		];
		$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$client->enableProfiler();
        $container = $client->getContainer();
		$client->request('POST', '/api/secure/registration', [], [], [], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$result = json_decode($response->getContent(), true);
		$this->assertSame($params['username'], $result['username']);
        $this->assertTrue($result['is_registration_complete']);
        /** @var Connection $conn */
		$conn = $container->get('doctrine.dbal.default_connection');
		$user = $conn->fetchAssoc('SELECT * FROM user WHERE username = ?', [$result['username']]);
		$this->assertSame($params['username'], $user['username']);
		$this->assertSame($params['first_name'], $user['firstName']);
		$this->assertSame($params['last_name'], $user['lastName']);
		$this->assertSame('regtest@mail.com', $user['email']);
		$this->assertSame($params['address1'], $user['address1']);
		$this->assertSame($params['address2'], $user['address2']);
		$this->assertSame($params['city'], $user['city']);
		$this->assertSame($params['state'], $user['state']);
		$this->assertSame($params['zip'], $user['zip']);
		$this->assertSame($params['country'], $user['country']);
		$this->assertSame(strtotime($params['birth']), strtotime($user['birth']));
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(UserEvents::REGISTRATION, $msg->getEventName());
        $this->assertInstanceOf(UserEvent::class, $msg->getEvent());
        $storage = $container->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('avatar_image_fs'));

		$client->request('POST', '/api/secure/login', ['username' => $params['username'], 'password' => $params['password']]);
        $response = $client->getResponse();
		$params = json_decode($response->getContent(), true);
		$this->assertNotEmpty($params['token']);
	}

    /**
     * @QueryCount(0)
     */
    public function testFacebookRegistrationWithWrongDataReturnsErrors(): void
    {
        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $serviceId = 'civix_core.facebook_api';
        $mock = $this->getMockBuilder(FacebookApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFacebookId'])
            ->getMock();
        $mock->expects($this->any())->method('getFacebookId')->will($this->returnValue('yyy'));
        $client->getContainer()->set($serviceId, $mock);
        $client->request('POST', '/api/secure/registration-facebook', [], [], [], json_encode(['email' => 'qwerty']));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        /** @var array $errors */
        $errors = $data['errors'];
        $expectedErrors = [
            null => 'Facebook token is not correct.',
            'facebook_id' => 'This value should not be blank.',
            'username' => 'This value should not be blank.',
            'first_name' => 'This value should not be blank.',
            'last_name' => 'This value should not be blank.',
            'email' => 'This value is not a valid email address.',
            'email_confirm' => 'The email fields must match.',
        ];
        $this->assertCount(count($expectedErrors), $errors);
        foreach ($errors as $error) {
            $this->assertEquals($expectedErrors[$error['property']], $error['message']);
        }
    }

    /**
     * @QueryCount(5)
     */
    public function testFacebookRegistrationIsOk(): void
    {
	    $this->loadFixtures([]);
		$faker = Factory::create();
        $email = 'reg.test+powerline@mail.com';
        $data = [
		    'facebook_token' => 'xxx',
            'facebook_id' => 'yyy',
            'facebook_link' => $faker->url,
			'username' => 'testUser1',
			'first_name' => $faker->firstName,
			'last_name' => $faker->lastName,
			'email' => $email,
			'email_confirm' => $email,
			'address1' => 'Bucklin',
			'address2' => $faker->address,
			'city' => 'Bucklin',
			'state' => 'KS',
			'country' => 'US',
			'birth' => $faker->date(),
		];
		$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->enableProfiler();
        $serviceId = 'civix_core.facebook_api';
        $mock = $this->getMockBuilder(FacebookApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFacebookId'])
            ->getMock();
        $mock->expects($this->any())->method('getFacebookId')->will($this->returnValue('yyy'));
        $client->getContainer()->set($serviceId, $mock);
		$client->request('POST', '/api/secure/registration-facebook', [], [], [], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('avatar_image_fs'));
        $result = json_decode($response->getContent(), true);
		$this->assertSame($data['username'], $result['username']);
		$this->assertFalse($result['is_registration_complete']);
		/** @var Connection $conn */
		$conn = $client->getContainer()->get('doctrine')->getConnection();
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
		$this->assertSame($data['country'], $user['country']);
		$this->assertSame(strtotime($data['birth']), strtotime($user['birth']));
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $messages = $collector->getData();
        $this->assertCount(1, $messages);
        $msg = unserialize($messages[0]['msg']);
        $this->assertSame(UserEvents::REGISTRATION, $msg->getEventName());
        $this->assertInstanceOf(UserEvent::class, $msg->getEvent());

        $client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
        $client->getContainer()->set($serviceId, $mock);
        $client->request('POST', '/api/secure/facebook/login', [
		    'facebook_token' => $data['facebook_token'],
            'facebook_id' => $data['facebook_id'],
        ]);
		$response = $client->getResponse();
		$data = json_decode($response->getContent(), true);
		$this->assertNotEmpty($data['token']);
	}

    /**
     * @QueryCount(4)
     */
    public function testForgotPassword(): void
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
                    $this->assertRegExp('=http://localhost/#/reset-password/[\w\d]+=', $params['link']);

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

    /**
     * @QueryCount(1)
     */
    public function testCheckResetToken(): void
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

    /**
     * @QueryCount(4)
     */
    public function testSaveNewPassword(): void
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
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $data = $conn->fetchAssoc('SELECT password, reset_password_token, reset_password_at FROM user WHERE id = ?', [$user->getId()]);
        $this->assertNotEquals($user->getPassword(), $data['password']);
        $this->assertNull($data['reset_password_token']);
        $this->assertNull($data['reset_password_at']);
	}

    /**
     * @QueryCount(1)
     */
    public function testUserTokenHeaderAuthentication(): void
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $server = ['HTTP_Token' => $user->getToken()];
        $client->request('GET', '/api/v2/user', [], [], $server);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @QueryCount(1)
     */
    public function testUserAuthorizationTypeHeaderAuthentication(): void
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $server = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user->getToken().'"'];
        $client->request('GET', '/api/v2/user', [], [], $server);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @QueryCount(1)
     */
    public function testUserAuthorizationBearerHeaderAuthentication(): void
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $server = ['HTTP_Authorization' => 'Bearer '.$user->getToken()];
        $client->request('GET', '/api/v2/user', [], [], $server);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
	}
}
<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\CropImage;
use Civix\CoreBundle\Service\FacebookApi;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Geocoder\Exception\NoResult;
use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Coordinates;
use Geocoder\Model\Country;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
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
		
		$this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class,
            LoadSuperuserData::class
		]);
	}

	public function tearDown()
	{
		$this->client = NULL;
        parent::tearDown();
    }
	
	public function testUserLoginWithoutCredentials()
	{
		$this->client->request('POST', self::API_LOGIN_ENDPOINT);
        $response = $this->client->getResponse();
        $this->assertEquals(401, $response->getStatusCode(), $response->getContent());
	}

    public function testUserLoginWithWrongCredentials()
    {
        $parameters = ['username' => 'user2', 'password' => 'user1'];
        $content = ['application/x-www-form-urlencoded'];

        $this->client->request('POST', self::API_LOGIN_ENDPOINT, $parameters, [], [], $content);
        $response = $this->client->getResponse();
        $this->assertEquals(401, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Authentication failed.', $data['message']);
    }
	
	public function testUserLoginWithCredentials()
	{
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

	public function testDisabledUserLoginFails()
	{
	    $em = $this->client->getContainer()->get('doctrine')->getManager();
	    /** @var User $user */
	    $user = $em->getRepository(User::class)->findOneBy(['username' => 'user1']);
	    $user->disable();
	    $em->persist($user);
	    $em->flush();
		$parameters = ['username' => 'user1', 'password' => 'user1'];
		$content = ['application/x-www-form-urlencoded'];

		$this->client->request('POST', self::API_LOGIN_ENDPOINT, $parameters, [], [], $content);
		$response = $this->client->getResponse();
		$this->assertEquals(401, $response->getStatusCode(), $response->getContent());
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

    /**
     * @QueryCount(11)
     * 1. Check username is free
     * 2. Check email is free
     * 3-5. Insert user in transaction
     * 6. Select deferred_invites
     * 7-9. Add discount code in transaction
     * 10. Add user report
     * 11. Check "Powerline Powerusers" group to join
     * @todo move all updates to one transaction
     */
	public function testRegistrationIsOk()
	{
		$faker = Factory::create();
        $path = 'http://example.com/image.jpg';
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
            'avatar_file_name' => $path,
		];
		$client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$service = $this->getMockBuilder(CropImage::class)
            ->disableOriginalConstructor()
            ->setMethods(['rebuildImage'])
            ->getMock();
		$service->expects($this->once())
            ->method('rebuildImage')
            ->with($this->anything(), $path)
            ->willReturnCallback(function ($tempFile) {
                return file_put_contents($tempFile, file_get_contents(__DIR__.'/../data/image.png'));
            });
        $container = $client->getContainer();
        $container->set('civix_core.crop_image', $service);
        $service = $this->getMockBuilder(CiceroApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepresentativesByLocation'])
            ->getMock();
        $service->expects($this->once())
            ->method('getRepresentativesByLocation');
        $client->getContainer()->set('civix_core.cicero_api', $service);
		$service = $this->createMock(Geocoder::class);
		$service->expects($this->exactly(2))
            ->method('geocode')
            ->with($data['address1'].','.$data['city'].','.$data['state'].','.$data['country'])
            ->willReturn(new AddressCollection([
                new Address(
                    new Coordinates(37.547242900000001, -99.634290100000001),
                    null,
                    null,
                    null,
                    '67834',
                    'Bucklin',
                    null,
                    new AdminLevelCollection([
                        new AdminLevel(1, 'Kansas', 'KS'),
                        new AdminLevel(2, 'Ford County', 'Ford County'),
                        new AdminLevel(3, 'Bucklin', 'Bucklin'),
                    ]),
                    new Country('United States', 'US')
                )
            ]));
		$client->getContainer()->set('bazinga_geocoder.geocoder', $service);
		$client->request('POST', '/api/secure/registration', [], [], [], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());

		$result = json_decode($response->getContent(), true);
		$this->assertSame($data['username'], $result['username']);
		/** @var Connection $conn */
		$conn = $container->get('doctrine.dbal.default_connection');
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
        $storage = $container->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('avatar_image_fs'));
        $code = $conn->fetchAssoc('SELECT * FROM discount_codes WHERE owner_id = ?', [$user['id']]);
        $this->assertNotEmpty($code['code']);
        $this->assertSame($client->getContainer()->getParameter('stripe_referral_code'), $code['original_code']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(User::class)->find($user['id']);
        $this->assertSame(37.547242900000001, $user->getLatitude());
        $this->assertSame(-99.634290100000001, $user->getLongitude());
        $report = $em->getRepository(UserReport::class)
            ->getUserReport($user);
        $this->assertEquals($user->getId(), $report[0]['user']);
        $this->assertEquals(0, $report[0]['followers']);
        $this->assertEquals([], $report[0]['representatives']);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups ug WHERE ug.user_id = ?', [$user->getId()]);
        $this->assertEquals(3, $count);

		$client->request('POST', '/api/secure/login', ['username' => $data['username'], 'password' => $data['password']]);
		$response = $client->getResponse();
		$data = json_decode($response->getContent(), true);
		$this->assertNotEmpty($data['token']);
	}

    public function testFacebookRegistrationWithWrongDataReturnsErrors()
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

    /**
     * @QueryCount(12)
     * @todo see testRegistrationIsOk
     */
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
        $mock = $this->getMockBuilder(FacebookApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFacebookId'])
            ->getMock();
        $mock->expects($this->any())->method('getFacebookId')->will($this->returnValue('yyy'));
        $client->getContainer()->set($serviceId, $mock);
        $service = $this->getMockBuilder(CiceroApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepresentativesByLocation'])
            ->getMock();
        $service->expects($this->once())
            ->method('getRepresentativesByLocation');
        $client->getContainer()->set('civix_core.cicero_api', $service);
        $service = $this->createMock(Geocoder::class);
        $service->expects($this->exactly(2))
            ->method('geocode')
            ->with($data['address1'].','.$data['city'].','.$data['state'].','.$data['country'])
            ->willThrowException(new NoResult());
        $client->getContainer()->set('bazinga_geocoder.geocoder', $service);
		$client->request('POST', '/api/secure/registration-facebook', [], [], [], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());

		$result = json_decode($response->getContent(), true);
		$this->assertSame($data['username'], $result['username']);
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
		$this->assertSame($data['zip'], $user['zip']);
		$this->assertSame($data['country'], $user['country']);
		$this->assertSame(strtotime($data['birth']), strtotime($user['birth']));
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(User::class)->find($user['id']);
        $this->assertNull($user->getLatitude());
        $this->assertNull($user->getLongitude());
        $report = $em->getRepository(UserReport::class)
            ->getUserReport($user);
        $this->assertEquals($user->getId(), $report[0]['user']);
        $this->assertEquals(0, $report[0]['followers']);
        $this->assertEquals([], $report[0]['representatives']);

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
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $data = $conn->fetchAssoc('SELECT password, reset_password_token, reset_password_at FROM user WHERE id = ?', [$user->getId()]);
        $this->assertNotEquals($user->getPassword(), $data['password']);
        $this->assertNull($data['reset_password_token']);
        $this->assertNull($data['reset_password_at']);
	}
}
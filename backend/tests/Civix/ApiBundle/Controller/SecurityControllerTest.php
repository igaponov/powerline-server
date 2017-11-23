<?php
namespace Tests\Civix\ApiBundle\Controller;

use Buzz\Client\ClientInterface;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\DataCollector\RabbitMQDataCollector;
use Civix\CoreBundle\Entity\RecoveryToken;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\Authy;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Faker\Factory;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Result;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use libphonenumber\PhoneNumber;
use Symfony\Bundle\FrameworkBundle\Client;
use Tests\Civix\CoreBundle\DataFixtures\ORM\LoadRecoveryTokenData;

class SecurityControllerTest extends WebTestCase
{
    private const API_ENDPOINT = '/api/v2/security/';

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

    public function testOAuthWithWrongCredentials()
    {
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'facebook');
        $response = $client->getResponse();
        $this->assertEquals(401, $response->getStatusCode(), $response->getContent());
    }

    public function testOAuthExistentUserWithCorrectCredentials()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $container = $client->getContainer();
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $container->set('hwi_oauth.http_client', $httpClient);

        /** @var ResourceOwnerInterface $resourceOwner */
        $resourceOwner = $container->get('hwi_oauth.resource_owner.facebook');
        $token = $this->getOAuth2Token();
        $params = ['code' => uniqid('', true)];
        $userProviderId = $user->getFacebookId();

        $httpClient->expects($this->at(0))->method('send')->will($this->returnCallback(
            $this->getAccessTokenCallback($resourceOwner, $token)
        ));
        $httpClient->expects($this->at(1))->method('send')->will($this->returnCallback(
            $this->getUserInformationCallback($resourceOwner, $userProviderId)
        ));

        $client->request('GET', self::API_ENDPOINT.'facebook', $params);
        $response = $client->getResponse();

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $response->getStatusCode(),
            $response->getContent()
        );

        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['token']);
        $this->assertEquals($user->getId(), $data['id']);
    }

    public function testOAuthNonExistentUserWithCorrectCredentials()
    {
        $faker = Factory::create();
        $client = $this->client;
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $container = $client->getContainer();
        $container->set('hwi_oauth.http_client', $httpClient);

        /** @var ResourceOwnerInterface $resourceOwner */
        $resourceOwner = $container->get('hwi_oauth.resource_owner.facebook');
        $token = $this->getOAuth2Token();
        $params = ['code' => uniqid('', true)];
        $userProviderId = $faker->bothify('?#?#?#?#?#?#?#?#');
        $httpClient->expects($this->at(0))->method('send')->will($this->returnCallback(
            $this->getAccessTokenCallback($resourceOwner, $token)
        ));
        $httpClient->expects($this->at(1))->method('send')->will($this->returnCallback(
            $this->getUserInformationCallback($resourceOwner, $userProviderId)
        ));

        $client->request('GET', self::API_ENDPOINT.'facebook', $params);
        $response = $client->getResponse();

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $response->getStatusCode(),
            $response->getContent()
        );

        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['token']);

        $user = $container->get('database_connection')->fetchColumn(
            'SELECT COUNT(*) FROM user WHERE facebook_id = ?', [$userProviderId]
        );
        $this->assertEquals(1, $user);
    }

    public function testOAuthNonExistentUserWithEmptyEmail()
    {
        $faker = Factory::create();
        $client = $this->client;
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $client->getContainer()->set('hwi_oauth.http_client', $httpClient);

        /** @var ResourceOwnerInterface $resourceOwner */
        $resourceOwner = $client->getContainer()->get('hwi_oauth.resource_owner.facebook');
        $token = $this->getOAuth2Token();
        $params = ['code' => uniqid('', true)];
        $userProviderId = $faker->bothify('?#?#?#?#?#?#?#?#');
        $httpClient->expects($this->at(0))->method('send')->will($this->returnCallback(
            $this->getAccessTokenCallback($resourceOwner, $token)
        ));
        $httpClient->expects($this->at(1))->method('send')->will($this->returnCallback(
            $this->getUserInformationCallback($resourceOwner, $userProviderId, null)
        ));

        $client->request('GET', self::API_ENDPOINT.'facebook', $params);
        $response = $client->getResponse();
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR,
            $response->getStatusCode(),
            $response->getContent()
        );
        $data = json_decode($response->getContent(), true);
        $this->assertContains(
            'Please make sure that verified email is connected to your Facebook account.',
            $data['message']
        );
    }

    public function testOAuthNonExistentUserWithDuplicateEmail()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $faker = Factory::create();
        $client = $this->client;
        $container = $client->getContainer();
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $container->set('hwi_oauth.http_client', $httpClient);

        /** @var ResourceOwnerInterface $resourceOwner */
        $resourceOwner = $container->get('hwi_oauth.resource_owner.facebook');
        $token = $this->getOAuth2Token();
        $params = ['code' => uniqid('', true)];
        $userProviderId = $faker->bothify('?#?#?#?#?#?#?#?#');

        $httpClient->expects($this->at(0))->method('send')->will($this->returnCallback(
            $this->getAccessTokenCallback($resourceOwner, $token)
        ));
        $httpClient->expects($this->at(1))->method('send')->will($this->returnCallback(
            $this->getUserInformationCallback(
                $resourceOwner,
                $userProviderId,
                $user->getEmail()
            )
        ));

        $client->request('GET', self::API_ENDPOINT.'facebook', $params);
        $response = $client->getResponse();

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $response->getStatusCode(),
            $response->getContent()
        );

        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['token']);
        $this->assertEquals($user->getId(), $data['id']);
    }

    public function testRegistration()
    {
        $this->loadFixtures([]);
        $client = $this->client;
        $service = $this->getMockBuilder(Authy::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkVerification'])
            ->getMock();
        $service->expects($this->once())
            ->method('checkVerification')
            ->with($this->isInstanceOf(PhoneNumber::class), '246135')
            ->willReturn(new Result(['success' => true]));
        $client->getContainer()->set('civix_core.service.authy', $service);
        $client->enableProfiler();
        $params = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'email' => 'john@doe.com',
            'country' => 'US',
            'zip' => '123456',
            'phone' => '+1-800-555-1111',
            'code' => '246135',
        ];
        $client->request('POST', self::API_ENDPOINT.'registration', [], [], [], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['id']);
        $this->assertNotEmpty($data['username']);
        $this->assertEmpty($data['token']);
        $this->assertTrue($data['is_registration_complete']);
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(UserEvents::REGISTRATION, $msg->getEventName());
        $this->assertInstanceOf(UserEvent::class, $msg->getEvent());
    }

    public function testLoginByPhone()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $client = $this->client;
        $container = $client->getContainer();
        $service = $this->getMockBuilder(GuzzleClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__call'])
            ->getMock();
        $service->expects($this->once())
            ->method('__call')
            ->with('startVerification', [[
                'country_code' => 1,
                'phone_number' => '234567890',
                'via' => 'call',
            ]])
            ->willReturn(['success' => true]);
        $container->set('civix_core.authy', $service);
        $client->request('POST', self::API_ENDPOINT.'login', [], [], [], json_encode(['phone' => '+1234567890']));
        $response = $client->getResponse();

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $response->getStatusCode(),
            $response->getContent()
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function testConfirmLoginByPhone()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $token = $user->getToken();
        /** @var User $user */
        $client = $this->client;
        $container = $client->getContainer();
        $service = $this->getMockBuilder(GuzzleClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__call'])
            ->getMock();
        $code = 'ZxC123';
        $service->expects($this->once())
            ->method('__call')
            ->with('checkVerification', [[
                'country_code' => 1,
                'phone_number' => '234567890',
                'verification_code' => $code,
            ]])
            ->willReturn(['success' => true]);
        $container->set('civix_core.authy', $service);
        $client->request('POST', self::API_ENDPOINT.'login', [], [], [], json_encode(['phone' => '+1234567890', 'code' => $code]));
        $response = $client->getResponse();

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $response->getStatusCode(),
            $response->getContent()
        );

        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['token']);
        $this->assertNotSame($token, $data['token']);
        $this->client->request('GET', '/api/v2/user', [], [], ['HTTP_TOKEN' => $data['token']]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
    }

    public function testRequestRecoverByEmail()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        /** @var User $user */
        $client = $this->client;
        $client->enableProfiler();
        $phone = $user->getPhone();
        $params = [
            'username' => $user->getUsername(),
            'phone' => '+'.$phone->getCountryCode().$phone->getNationalNumber(),
            'zip' => $user->getZip(),
            'token' => uniqid(),
        ];
        $client->request('POST', self::API_ENDPOINT.'recovery', [], [], [], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('ok', $response->getContent());
        /** @var \Swift_Message[] $messages */
        /** @noinspection PhpUndefinedMethodInspection */
        $messages = $client->getProfile()->getCollector('swiftmailer')->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($user->getEmail(), $messages[0]->getTo());
        $this->assertSame('Powerline Account Verification (Action Required)', $messages[0]->getSubject());
        $this->assertRegExp('{http://localhost/security/recovery/[\w\+\=\/]+}', $messages[0]->getBody());
    }

    public function testRecoverByEmail()
    {
        $repository = $this->loadFixtures([
            LoadRecoveryTokenData::class,
        ])->getReferenceRepository();
        /** @var RecoveryToken $token */
        $token = $repository->getReference('recovery_token_2');
        $user = $token->getUser();
        /** @var User $user */
        $client = $this->client;
        $params = [
            'username' => $user->getUsername(),
            'token' => 'device2',
        ];
        $client->request('POST', self::API_ENDPOINT.'recovery', [], [], [], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['token']);
        $this->assertNotSame($user->getToken(), $data['token']);
    }

    private function getOAuth2Token()
    {
        return [
            'access_token' => 'JvyS7DO2qd6NNTsXJ4E7zA',
        ];
    }

    private function getAccessTokenCallback($resourceOwner, $token)
    {
        $options = $this->getProperty($resourceOwner, 'options');
        return function (Request $request, Response $response) use ($token, $options) {
            $this->assertContains($options['access_token_url'], $request->getUrl());
            $response->setContent(json_encode($token));
        };
    }

    private function getUserInformationCallback($resourceOwner, $userProviderId, $email = 'oauth@mail.com')
    {
        $faker = Factory::create();
        $paths = $this->getProperty($resourceOwner, 'paths');
        $data = [
            $paths['identifier'] => $userProviderId,
        ];
        if (isset($paths['firstname'])) {
            $data[$paths['firstname']] = $faker->firstName;
        }
        if (isset($paths['lastname'])) {
            $data[$paths['lastname']] = $faker->lastName;
        }
        if (isset($paths['realname'])) {
            $data[$paths['realname']] = $faker->name;
        }
        if ($email && isset($paths['email'])) {
            $data[$paths['email']] = $email;
        }
        if (isset($paths['nickname'])) {
            $data[$paths['nickname']] = $faker->userName;
        }
        if (isset($paths['profilepicture'])) {
            $data[$paths['profilepicture']] = $faker->url;
        }
        $options = $this->getProperty($resourceOwner, 'options');
        return function (Request $request, Response $response) use ($data, $options) {
            $this->assertContains($options['infos_url'], $request->getUrl());
            $response->setContent(json_encode($data));
        };
    }

    private function getProperty($obj, $propertyName) {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }
}
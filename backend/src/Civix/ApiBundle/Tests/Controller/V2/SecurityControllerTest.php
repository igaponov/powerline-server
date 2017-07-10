<?php
namespace ToughDomains\Bundle\SecurityBundle\Tests\Controller;

use Buzz\Client\ClientInterface;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Faker\Factory;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use Symfony\Bundle\FrameworkBundle\Client;

class SecurityControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/security/facebook';

    /**
     * @var Client
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

    public function testOAuthWithWrongCredentials()
    {
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT);
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

        $client->request('GET', self::API_ENDPOINT, $params);
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

        $client->request('GET', self::API_ENDPOINT, $params);
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

        $client->request('GET', self::API_ENDPOINT, $params);
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

        $client->request('GET', self::API_ENDPOINT, $params);
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
        if (isset($paths['email']) && $email) {
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
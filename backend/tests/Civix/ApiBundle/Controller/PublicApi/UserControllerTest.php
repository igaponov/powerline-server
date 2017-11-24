<?php

namespace Test\Civix\ApiBundle\Controller\PublicApi;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use libphonenumber\PhoneNumber;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\PropertyAccess\PropertyAccess;

class UserControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadUserData::class,
        ]);
    }

    public function setUp(): void
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown(): void
    {
        $this->client = NULL;
        parent::tearDown();
    }

    /**
     * @param string $reference
     * @param string $attribute
     * @dataProvider getReferences
     */
    public function testGetUserAction(string $reference, string $attribute)
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference($reference);
        $accessor = PropertyAccess::createPropertyAccessor();
        $params = [$attribute => $accessor->getValue($user, $attribute)];
        if ($params[$attribute] instanceof PhoneNumber) {
            $params[$attribute] = '+'.$params[$attribute]->getCountryCode().$params[$attribute]->getNationalNumber();
        }
        $this->client->request('GET', '/api-public/users', $params, []);
        $response = $this->client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEmpty($response->getContent());
    }

    public function getReferences()
    {
        return [
            'username' => ['user_1', 'username'],
            'email' => ['user_2', 'email'],
            'phone' => ['user_3', 'phone'],
        ];
    }

    /**
     * @param $params
     * @dataProvider getAttributes
     */
    public function testGetUserReturns404($params)
    {
        $this->client->request('GET', '/api-public/users', $params, []);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }

    public function getAttributes()
    {
        return [
            'username' => [['username' => uniqid()]],
            'email' => [['email' => uniqid()]],
        ];
    }

    public function testGetUserReturns400()
    {
        $this->client->request('GET', '/api-public/users', [], []);
        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
    }
}

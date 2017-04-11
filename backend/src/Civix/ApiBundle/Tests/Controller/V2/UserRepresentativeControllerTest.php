<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadStateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadUserReportData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class UserRepresentativeControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/representatives';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

    /**
     * @QueryCount(8)
     */
    public function testGetRepresentatives()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        $representative = $repository->getReference('representative_jb');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
        $this->assertSame($representative->getId(), $data['payload'][0]['id']);
        $result = $client->getContainer()->get('doctrine.dbal.default_connection')
            ->fetchAssoc('SELECT * FROM karma');
        $this->assertArraySubset([
            'user_id' => $user->getId(),
            'points' => 25,
            'type' => Karma::TYPE_REPRESENTATIVE_SCREEN,
        ], $result);
    }

    /**
     * @QueryCount(8)
     */
    public function testGetRepresentativesIsEmpty()
    {
        $repository = $this->loadFixtures([
            LoadKarmaData::class,
            LoadUserReportData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_3');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(50, $data['items']);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
        $conn = $client->getContainer()
            ->get('doctrine.dbal.default_connection');
        $results = $conn->fetchAll(
            'SELECT * FROM karma WHERE user_id = ? AND type = ?',
            [$user->getId(), Karma::TYPE_REPRESENTATIVE_SCREEN]
        );
        $this->assertCount(1, $results, "Should add points for representative's view only once");
        $sum = $conn->fetchColumn('SELECT karma FROM user_report WHERE user_id = ?', [$user->getId()]);
        $this->assertEquals(45, $sum);
    }

    public function testCreateRepresentativeWithErrors()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $errors = [
            'official_title' => 'This value should not be blank.',
            'city' => 'This value should not be blank.',
            'state' => 'This value should not be blank.',
            'country' => 'This value should not be blank.',
            'phone' => 'This value should not be blank.',
            'private_phone' => 'This value should not be blank.',
            'email' => 'This value should not be blank.',
            'private_email' => 'This value should not be blank.',
            'avatar' => 'The mime type of the file is invalid ("text/x-php"). Allowed mime types are "image/png", "image/jpeg", "image/jpg".',
        ];
        $client = $this->client;
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['avatar' => base64_encode(file_get_contents(__FILE__))]));
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, $errors);
    }

    public function testCreateRepresentativeIsOk()
    {
        $this->loadFixtures([
            LoadStateData::class,
            LoadUserData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $params = [
            'official_title' => $faker->lexify('????????'),
            'city' => 'Washington',
            'state' => 'WA',
            'country' => 'US',
            'phone' => $faker->phoneNumber,
            'email' => $faker->email,
        ];
        $privateParams = [
            'private_phone' => $faker->phoneNumber,
            'private_email' => $faker->companyEmail,
            'avatar' => base64_encode(file_get_contents(__DIR__.'/../../data/image.png')),
        ];
        $client = $this->client;
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(array_merge($params, $privateParams)));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        foreach ($params as $property => $value) {
            $this->assertSame($value, $data[$property]);
        }
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM representatives r WHERE r.privatePhone = ? AND r.privateEmail = ?', [$privateParams['private_phone'], $privateParams['private_email']]);
        $this->assertEquals(1, $count);
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('avatar_representative_fs'));
    }
}
<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadCiceroRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Symfony\Bundle\FrameworkBundle\Client;

class RepresentativeControllerTest  extends WebTestCase
{
    const API_ENDPOINT = '/api/representatives/';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        // Creates a initial client
        $this->client = static::createClient();
    }

    public function tearDown()
    {
        $this->client = NULL;
        parent::tearDown();
    }

    public function testGetRepresentatives()
    {
        $this->loadFixtures([
            LoadRepresentativeData::class,
            LoadCiceroRepresentativeData::class,
        ]);
        $this->client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(
            [
                [
                    'title' => 'Local',
                    'representatives' => [
                        [
                            'representative' => [
                                'first_name' => 'User',
                                'last_name' => 'One',
                                'id' => 1,
                                'official_title' => 'Vice President',
                            ],
                        ],
                        [
                            'representative' => [
                                'first_name' => 'user',
                                'last_name' => '3',
                                'id' => 3,
                                'official_title' => 'Software Engineer',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Office of the President',
                    'representatives' => [
                        [
                            'storage_id' => 44926,
                            'first_name' => 'Joseph',
                            'last_name' => 'Biden',
                            'official_title' => 'Vice President',
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    public function testGetEmptyRepresentatives()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $this->client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(0, $data);
    }

    public function testGetRepresentativeAction()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Representative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->client->request('GET', self::API_ENDPOINT.'info/'.$representative->getId().'/0', [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($representative->getId(), $data['id']);
        $this->assertEquals($representative->getUser()->getFirstName(), $data['first_name']);
        $this->assertEquals($representative->getUser()->getLastName(), $data['last_name']);
        $this->assertEquals($representative->getOfficialTitle(), $data['official_title']);
        $this->assertEquals($representative->getCity(), $data['city']);
        $this->assertEquals($representative->getCountry(), $data['country']);
        $this->assertEquals($representative->getPhone(), $data['phone']);
        $this->assertEquals($representative->getEmail(), $data['email']);
    }

    public function testGetNonExistentRepresentative()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadCiceroRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var CiceroRepresentative $representative */
        $representative = $repository->getReference('cicero_representative_jb');
        $this->client->request('GET', self::API_ENDPOINT.'info/1/'.$representative->getId(), [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }

    public function testGetCiceroRepresentativeAction()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadCiceroRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var CiceroRepresentative $representative */
        $representative = $repository->getReference('cicero_representative_jb');
        $this->client->request('GET', self::API_ENDPOINT.'info/0/'.$representative->getId(), [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($representative->getId(), $data['storage_id']);
        $this->assertEquals($representative->getFirstName(), $data['first_name']);
        $this->assertEquals($representative->getLastName(), $data['last_name']);
        $this->assertEquals($representative->getOfficialTitle(), $data['official_title']);
        $this->assertEquals($representative->getCity(), $data['city']);
        $this->assertEquals($representative->getPhone(), $data['phone']);
        $this->assertEquals($representative->getEmail(), $data['email']);
    }
}
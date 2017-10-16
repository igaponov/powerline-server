<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Serializer\Adapter\BillAdapter;
use Civix\CoreBundle\Serializer\Adapter\CommitteeAdapter;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
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
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        $representative = $repository->getReference('representative_jb');
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
                                'avatar_file_path' => "https://powerline-dev.imgix.net/avatars/representatives/{$representative->getAvatarFileName()}?ixlib=php-1.1.0",
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
                            'storage_id' => 123543,
                            'first_name' => 'Barack',
                            'last_name' => 'Obama',
                            'official_title' => 'President',
                        ],
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

    public function testGetRepresentative()
    {
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
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
        $this->assertEquals("https://powerline-dev.imgix.net/avatars/representatives/{$representative->getAvatarFileName()}?ixlib=php-1.1.0", $data['avatar_file_path']);
    }

    public function testGetNonExistentRepresentative()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Representative $representative */
        $representative = $repository->getReference('cicero_representative_jb');
        $this->client->request('GET', self::API_ENDPOINT.'info/1/'.$representative->getId(), [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }

    public function testGetCiceroRepresentative()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Representative $representative */
        $representative = $repository->getReference('cicero_representative_jb');
        $this->client->request('GET', self::API_ENDPOINT.'info/0/'.$representative->getId(), [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($representative->getCiceroId(), $data['storage_id']);
        $this->assertEquals($representative->getFirstName(), $data['first_name']);
        $this->assertEquals($representative->getLastName(), $data['last_name']);
        $this->assertEquals($representative->getOfficialTitle(), $data['official_title']);
        $this->assertEquals($representative->getCity(), $data['city']);
        $this->assertEquals($representative->getPhone(), $data['phone']);
        $this->assertEquals($representative->getEmail(), $data['email']);
    }

    public function testGetCommitteeInfo()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Representative $representative */
        $representative = $repository->getReference('cicero_representative_jb');
        $mock = $this->getServiceMockBuilder('civix_core.openstates_api')
            ->setMethods(['getCommiteeMembership'])
            ->disableOriginalConstructor()
            ->getMock();
        $committee = [
            'committee_id' => 'co_id',
            'committee' => 'committee1',
            'subcommittee' => 'subcommittee1',
            'position' => 'pos1',
        ];
        $mock->expects($this->once())
            ->method('getCommiteeMembership')
            ->with('os_id_01')
            ->willReturn(new CommitteeAdapter((object)$committee));
        $this->client->getContainer()->set('civix_core.openstates_api', $mock);
        $this->client->request('GET', self::API_ENDPOINT.'info/committee/'.$representative->getId(), [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($committee['committee_id'], $data['id']);
        $this->assertSame($committee['committee'], $data['committee']);
        $this->assertSame($committee['subcommittee'], $data['subcommittee']);
        $this->assertSame($committee['position'], $data['position']);
    }

    public function testGetSponsoredBills()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Representative $representative */
        $representative = $repository->getReference('cicero_representative_jb');
        $mock = $this->getServiceMockBuilder('civix_core.openstates_api')
            ->setMethods(['getBillsBySponsorId'])
            ->disableOriginalConstructor()
            ->getMock();
        $bill = [
            'id' => 'b_id',
            'title' => 'bill1',
            'sources' => [(object)['url' => 'http://bill.com']],
        ];
        $mock->expects($this->once())
            ->method('getBillsBySponsorId')
            ->with('os_id_01')
            ->willReturn(new BillAdapter((object)$bill));
        $this->client->getContainer()->set('civix_core.openstates_api', $mock);
        $this->client->request('GET', self::API_ENDPOINT.'info/sponsored-bills/'.$representative->getId(), [], [], ['HTTP_Authorization' => 'Bearer type="user" token="user1"']);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($bill['id'], $data['id']);
        $this->assertSame($bill['title'], $data['title']);
        $this->assertSame($bill['sources'][0]->url, $data['url']);
    }
}
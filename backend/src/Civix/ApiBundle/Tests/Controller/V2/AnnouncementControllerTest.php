<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadDistrictData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class AnnouncementControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/announcements';

    /**
     * @var null|Client
     */
    private $client = null;

    /**
     * @var ReferenceRepository
     */
    private $repository;

    public function setUp()
    {
        $this->repository = $this->loadFixtures([
            LoadUserData::class,
            LoadDistrictData::class,
            LoadRepresentativeData::class,
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function tearDown()
    {
        // Creates a initial client
        $this->client = NULL;
    }

    public function testGetAnnouncementsIsOk()
    {
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
        $announcement = $this->repository->getReference('announcement_jb_3');
        $this->assertEquals($announcement->getContent(), $data['payload'][0]['content_parsed']);
    }

    public function testGetAnnouncementsWithStartParameterIsOk()
    {
        $client = $this->client;
        $date = new \DateTime('-2 months');
        $client->request('GET', self::API_ENDPOINT, ['start' => $date->format('Y-m-d H:i:s')], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
        $announcement1 = $this->repository->getReference('announcement_jb_2');
        $announcement2 = $this->repository->getReference('announcement_jb_3');
        foreach ($data['payload'] as $item) {
            $this->assertThat(
                $item['content_parsed'],
                $this->logicalOr($announcement1->getContent(), $announcement2->getContent())
            );
        }
    }

    public function testGetAnnouncementsIsEmpty()
    {
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="testuserbookmark1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }
}
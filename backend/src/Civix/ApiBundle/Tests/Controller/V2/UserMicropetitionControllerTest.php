<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserMicropetitionControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/micro-petitions';

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
            LoadGroupData::class,
            LoadUserGroupData::class,
            LoadMicropetitionData::class,
            LoadMicropetitionAnswerData::class,
        ])->getReferenceRepository();
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function testGetMicropetitions()
    {
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(5, $data['totalItems']);
        $this->assertCount(5, $data['payload']);
    }

    public function testGetMicropetitionsEmpty()
    {
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }
}
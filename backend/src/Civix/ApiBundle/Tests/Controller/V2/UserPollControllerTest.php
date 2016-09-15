<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\DBAL\Connection;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPollControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/polls';

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
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
            LoadPollSubscriberData::class,
        ])->getReferenceRepository();
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown()
    {
        $this->client = null;
        $this->repository = null;
        parent::tearDown();
    }

    public function testSubscribeToPoll()
    {
        $poll = $this->repository->getReference('group_question_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$poll->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM poll_subscribers WHERE question_id = ?',
            [$poll->getId()]
        );
        $this->assertEquals(2, $count);
    }

    public function testSubscribeToPollAccessDenied()
    {
        $petition = $this->repository->getReference('group_question_3');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUnsubscribeFromPoll()
    {
        $client = $this->client;
        $poll = $this->repository->getReference('group_question_3');
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$poll->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM poll_subscribers WHERE question_id = ?',
            [$poll->getId()]
        );
        $this->assertEquals(2, $count);
    }

    public function testUnsubscribeFromNonSubscribedPoll()
    {
        $client = $this->client;
        $petition = $this->repository->getReference('group_question_3');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }
}
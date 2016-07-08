<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPollAnswerControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/poll-answers';

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
            LoadQuestionAnswerData::class,
        ])->getReferenceRepository();
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function testGetPollAnswers()
    {
        $answer = $this->repository->getReference('question_answer_2');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
        $this->assertSame($answer->getId(), $data['payload'][0]['id']);
    }

    public function testGetPollAnswersFilteredByEndDate()
    {
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, ['start' => date('Y-m-d H:i:s')], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }
}
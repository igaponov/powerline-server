<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadPollCommentRateData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Civix\ApiBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Question\Group as GroupQuestion;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class PollControllerTest extends WebTestCase
{
	const API_POLL_QUESTION_NEW_ENDPOINT = '/api/poll/question/new';

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
		$this->client = $this->makeClient();
        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

	public function tearDown()
	{
		$this->client = NULL;
		$this->em = NULL;
        parent::tearDown();
    }
	
	/**
	 * group api
	 */
	public function testAddNewQuestion()
	{
        $fixtures = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class,
            LoadSuperuserData::class
        ]);

        $reference = $fixtures->getReferenceRepository();

        /** @var Group $group */
        $group = $reference->getReference('group');
        $group->setOwner($reference->getReference('user_1'));
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($group);
        $em->flush();

        $group_token = $this->getUserToken($group->getUsername(), LoadGroupFollowerTestData::GROUP_PASSWORD);

		$this->assertNotEmpty($group_token, 'Login token should not empty');
		
		// Create a request scope context that allows serialize the question object

		$request = Request::create( '/' );
		
		$request->setSession( new Session() );
		$container->enterScope('request');
		
		$container->set('request', $request, 'request');
		
		$question = new GroupQuestion();
		$question->setOwner($group);

		/*
		 {"options":[],"educational_context":[],"is_answered":false,"cached_hash_tags":[],"user":{"id":370,"type":"group","group_type":0,"avatar_file_path":"http:\/\/localhost\/bundles\/civixfront\/img\/default_group.png"}}
		 */
		$content = $this->jmsSerialization($question, ['api-poll']);

		$this->client->request('PUT', self::API_POLL_QUESTION_NEW_ENDPOINT, [], [], ['HTTP_Token' => $group_token], $content);
		
		$response = $this->client->getResponse();
		
		//{"id":2,"options":[],"educational_context":[],"created_at":"Wed, 23 Mar 2016 02:25:23 +0000","is_answered":false,"cached_hash_tags":[]}
		$content = json_decode($response->getContent());

		$this->assertNotEmpty($content->created_at, 'The question object should have been created');
		
		// @todo implement more checks here
	
		$this->assertEquals(
				200,
				$response->getStatusCode(),
				'Should be authorized'
				);
	}

    /**
     * @param $action
     * @dataProvider getActions
     */
    public function testRateComment($action)
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadQuestionCommentData::class,
            LoadPollCommentRateData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_3');
        $client = $this->client;
        $uri = str_replace(['{id}', '{action}'], [$comment->getId(), $action], '/api/poll/comments/rate/{id}/{action}');
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($action == 'up' ? 2 : 0, $data['rate_sum']);
        $this->assertEquals(3, $data['rates_count']);
        $this->assertEquals($action == 'up' ? 1 : -1, $data['rate_status']);
	}

    public function getActions()
    {
        return ['up' => ['up'], 'down' => ['down']];
	}

    public function testDeleteRate()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadQuestionCommentData::class,
            LoadPollCommentRateData::class,
        ])->getReferenceRepository();
        $comment = $repository->getReference('question_comment_1');
        $client = $this->client;
        $uri = str_replace(['{id}', '{action}'], [$comment->getId(), 'delete'], '/api/poll/comments/rate/{id}/{action}');
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(1, $data['rate_sum']);
        $this->assertEquals(2, $data['rates_count']);
        $this->assertEquals(0, $data['rate_status']);
    }
}

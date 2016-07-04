<?php
namespace Civix\ApiBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;

use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Civix\ApiBundle\Tests\DataFixtures\ORM\LoadSuperuserData;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Group as GroupQuestion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class PollControllerTest extends WebTestCase
{
	const API_POLL_QUESTION_NEW_ENDPOINT = '/api/poll/question/new';

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;
	
	private $client = null;

	public function setUp()
	{
		// Creates a initial client
		$this->client = static::createClient();

		/** @var AbstractExecutor $fixtures */
		$fixtures = $this->loadFixtures([
				LoadUserData::class,
				LoadGroupFollowerTestData::class,
				LoadUserGroupFollowerTestData::class,
				LoadSuperuserData::class
		]);
		
		$reference = $fixtures->getReferenceRepository();
		
		$this->em = $this->getContainer()->get('doctrine')->getManager();
		
		$this->group = $reference->getReference('group');

		$this->group_token = $this->getUserToken($this->group->getUsername(), LoadGroupFollowerTestData::GROUP_PASSWORD);
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}
	
	/**
	 * group api
	 */
	public function testAddNewQuestion()
	{
		$this->assertNotEmpty($this->group_token, 'Login token should not empty');
		
		// Create a request scope context that allows serialize the question object
		$container = $this->getContainer();
		
		$request = Request::create( '/' );
		
		$request->setSession( new Session() );
		$container->enterScope('request');
		
		$container->set('request', $request, 'request');
		
		$question = new GroupQuestion();
		$question->setUser($this->group);

		/*
		 {"options":[],"educational_context":[],"is_answered":false,"cached_hash_tags":[],"user":{"id":370,"type":"group","group_type":0,"avatar_file_path":"http:\/\/localhost\/bundles\/civixfront\/img\/default_group.png"}}
		 */
		$content = $this->jmsSerialization($question, ['api-poll']);

		$this->client->request('PUT', self::API_POLL_QUESTION_NEW_ENDPOINT, [], [], ['HTTP_Token' => $this->group_token], $content);
		
		$request = $this->client->getRequest();
		
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
}

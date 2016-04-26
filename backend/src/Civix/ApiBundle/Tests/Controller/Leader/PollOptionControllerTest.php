<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Faker\Factory;
use Symfony\Component\BrowserKit\Client;

class PollOptionControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api-leader/options';
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var Client
	 */
	private $client = null;

	/**
	 * @var ProxyReferenceRepository
	 */
	private $repository;

	public function setUp()
	{
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

		$this->repository = $this->loadFixtures([
			LoadUserData::class,
			LoadGroupData::class,
			LoadGroupQuestionData::class,
			LoadQuestionAnswerData::class,
			LoadQuestionCommentData::class,
		])->getReferenceRepository();

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testUpdateOptionReturnsErrors()
	{
		/** @var Question $question */
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode(['value' => '']));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		$this->assertEquals(['This value should not be blank.'], $children['value']['errors']);
	}

	public function testUpdateCommentWithWrongCredentialsReturnsException()
	{
		/** @var Question $question */
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateOptionIsOk()
	{
		$faker = Factory::create();
		$params = [
			'value' => $faker->word,
		];
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
		$this->assertSame($params['value'], $data['value']);
	}

	public function testDeleteOptionWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$question = $this->repository->getReference('group-question');
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testDeleteOptionIsOk()
	{
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}
}

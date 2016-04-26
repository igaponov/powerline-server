<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\Poll\Question\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Faker\Factory;
use Symfony\Component\BrowserKit\Client;

class PollControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api-leader/polls';
	
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

	public function testGetPollAccessDenied()
	{
		/** @var Group $group */
		$group = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testGetPollsIsOk()
	{
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['type' => 'group'], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

	public function testGetPollAnswersIsOk()
	{
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/answers', ['type' => 'group'], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

	public function testGetPollCommentsIsOk()
	{
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/comments', ['type' => 'group'], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

	public function testGetPollIsOk()
	{
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreatePollReturnsErrors($params, $errors)
	{
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
	}

	/**
	 * @param $params
	 * @dataProvider getValidParams
	 */
	public function testCreatePollIsOk($params)
	{
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($params as $param => $value) {
			if (isset($data[$param])) {
				$this->assertSame($value, $data[$param]);
			}
		}
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParamsForUpdate
	 */
	public function testUpdatePollReturnsErrors($params, $errors)
	{
		$client = $this->client;
		$question = $this->repository->getReference('group-question');
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
	}

	public function testUpdatePollWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$question = $this->repository->getReference('group-question');
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdatePollIsOk()
	{
		$faker = Factory::create();
		$params = [
			'subject' => $faker->sentence,
			'report_recipient_group' => $faker->word,
		];
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
		$this->assertSame($params['subject'], $data['subject']);
	}

	public function getInvalidParams()
	{
		return [
			'empty type' => [
				[],
				[
					'type' => 'This value should not be blank.',
				]
			],
			'empty subject' => [
				[
					'type' => 'group',
				],
				[
					'subject' => 'This value should not be blank.',
				]
			],
		];
	}

	public function getInvalidParamsForUpdate()
	{
		return [
			'empty subject' => [
				[
					'subject' => '',
				],
				[
					'subject' => 'This value should not be blank.',
				]
			],
		];
	}

	public function getValidParams()
	{
		$faker = Factory::create();
		return [
			'group' => [
				[
					'type' => 'group',
					'subject' => $faker->sentence,
					'report_recipient_group' => $faker->word,
				]
			],
			'news' => [
				[
					'type' => 'news',
					'subject' => $faker->sentence,
					'report_recipient_group' => $faker->word,
				]
			],
			'event' => [
				[
					'type' => 'event',
					'subject' => $faker->sentence,
					'report_recipient_group' => $faker->word,
					'title' => $faker->sentence,
					'is_allow_outsiders' => $faker->boolean(),
					'started_at' => date('D, d M Y H:i:s', time() + 100000),
					'finished_at' => date('D, d M Y H:i:s', time() + 300000),
				]
			],
			'payment_request' => [
				[
					'type' => 'payment_request',
					'subject' => $faker->sentence,
					'report_recipient_group' => $faker->word,
					'title' => $faker->sentence,
					'is_allow_outsiders' => $faker->boolean(),
					'is_crowdfunding' => $faker->boolean(),
					'crowdfunding_goal_amount' => $faker->randomDigit,
					'crowdfunding_deadline' => date('D, d M Y H:i:s', time() + 500000),
					'is_crowdfunding_completed' => $faker->boolean(),
					'crowdfunding_pledged_amount' => $faker->randomDigit,
				]
			],
			'petition' => [
				[
					'type' => 'petition',
					'subject' => $faker->sentence,
					'report_recipient_group' => $faker->word,
					'is_outsiders_sign' => $faker->boolean(),
					'petition_title' => $faker->sentence,
					'petition_body' => $faker->text,
				]
			],
		];
	}

	public function testDeletePollWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$question = $this->repository->getReference('group-question');
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testDeletePollIsOk()
	{
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}

	public function testCreatePollOptionReturnsErrors()
	{
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT.'/'.$question->getId().'/options', [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		$this->assertEquals(['This value should not be blank.'], $children['value']['errors']);
	}

	public function testCreatePollOptionIsOk()
	{
		$question = $this->repository->getReference('group-question');
		$client = $this->client;
		$value = 'some_value';
		$client->request('POST', self::API_ENDPOINT.'/'.$question->getId().'/options', [], [], ['HTTP_Token'=>'secret_token'], json_encode(['value' => $value]));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($value, $data['value']);
	}
}

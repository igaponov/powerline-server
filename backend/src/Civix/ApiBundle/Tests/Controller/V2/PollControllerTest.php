<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Question\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Faker\Factory;
use Symfony\Component\BrowserKit\Client;

class PollControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/polls';
	
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
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetPollAccessDenied()
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		/** @var Group $group */
		$group = $repository->getReference('group_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testGetPollAnswersIsOk()
	{
        $repository = $this->loadFixtures([
            LoadQuestionAnswerData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('group_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/answers', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
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
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('group_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/comments', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForGetRequest
     */
	public function testGetPollIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParamsForUpdate
	 */
	public function testUpdatePollReturnsErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference('group_question_4');
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
        $this->assertSame(['Poll is already published'], $data['errors']['errors']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testUpdatePollWithWrongCredentialsReturnsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference($reference);
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testUpdatePollIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$faker = Factory::create();
		$params = [
			'subject' => $faker->sentence,
			'report_recipient_group' => $faker->word,
		];
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
		$this->assertSame($params['subject'], $data['subject']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testDeletePollWithWrongCredentialsReturnsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference($reference);
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testDeletePollIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testCreatePollOptionReturnsErrors($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT.'/'.$question->getId().'/options', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		$this->assertEquals(['This value should not be blank.'], $children['value']['errors']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testCreatePollOptionIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
		$value = 'some_value';
		$client->request('POST', self::API_ENDPOINT.'/'.$question->getId().'/options', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode(['value' => $value]));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($value, $data['value']);
	}

    public function getValidPollCredentialsForGetRequest()
    {
        return [
            'owner' => ['user1', 'group_question_1'],
            'manager' => ['user2', 'group_question_1'],
            'member' => ['user4', 'group_question_1'],
        ];
    }

    public function getInvalidPollCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_question_1'],
            'outlier' => ['user1', 'group_question_2'],
        ];
    }

    public function getValidPollCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user1', 'group_question_1'],
            'manager' => ['user2', 'group_question_1'],
        ];
    }
}

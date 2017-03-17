<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Group;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\Stripe\Charge;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupNewsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupPaymentRequestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData as LoadGroupQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData as LoadGroupQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadFieldValueData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupRepresentativesData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadStripeCustomerUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Representative\LoadRepresentativeNewsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Representative\LoadRepresentativePaymentRequestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Representative\LoadRepresentativeQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Representative\LoadQuestionAnswerData as LoadRepresentativeQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Representative\LoadQuestionCommentData as LoadRepresentativeQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountRepresentativeData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

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
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		$this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

	public function testGetGroupPollAccessDenied()
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		/** @var Group $poll */
		$poll = $repository->getReference('group_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$poll->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testGetRepresentativePollAccessDenied()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		/** @var Group $poll */
		$poll = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$poll->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @QueryCount(4)
     */
	public function testGetGroupPollAnswersIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionAnswerData::class,
        ])->getReferenceRepository();
		$poll = $repository->getReference('group_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$poll->getId().'/answers', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

    /**
     * @QueryCount(4)
     */
	public function testGetRepresentativePollAnswersIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionAnswerData::class,
        ])->getReferenceRepository();
		$poll = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$poll->getId().'/answers', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

    /**
     * @QueryCount(4)
     */
	public function testGetGroupPollAnswersFollowingIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadGroupQuestionAnswerData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('group_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/answers', ['following' => true], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

    /**
     * @QueryCount(4)
     */
	public function testGetRepresentativePollAnswersFollowingIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadRepresentativeQuestionAnswerData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/answers', ['following' => true], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

    /**
     * @QueryCount(4)
     */
	public function testGetGroupPollAnswersFollowingOutsideIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadGroupQuestionAnswerData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('group_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/answers', ['following' => false], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
	}

    /**
     * @QueryCount(4)
     */
	public function testGetRepresentativePollAnswersFollowingOutsideIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadRepresentativeQuestionAnswerData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/answers', ['following' => false], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
	}

	public function testGetGroupPollCommentsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionCommentData::class,
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

	public function testGetRepresentativePollCommentsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionCommentData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('representative_question_1');
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
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForGetRequest
     */
	public function testGetGroupPollIsOk($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures)
        )->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
		$this->assertNull($data['answer']);
	}

	public function testGetAnsweredGroupPollIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadQuestionAnswerData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('group_question_1');
        $answer = $repository->getReference('question_answer_2');
        $client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
		$this->assertEquals($answer->getId(), $data['answer']['id']);
		$this->assertEquals($answer->getOption()->getId(), $data['answer']['option']['id']);
		$this->assertEquals($answer->getComment(), $data['answer']['comment']);
	}

	public function testGetRepresentativePollIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
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
	public function testUpdateGroupPollReturnsErrors($params, $errors)
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

    /**
     * @param array $params
     * @param array $errors
     * @dataProvider getInvalidParamsForUpdate
     */
	public function testUpdateRepresentativePollReturnsErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference('representative_question_4');
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
	public function testUpdateGroupPollWithWrongCredentialsReturnsException($user, $reference)
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

	public function testUpdateRepresentativePollWithWrongCredentialsReturnsException()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference('representative_question_1');
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testUpdateGroupPollIsOk($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures)
        )->getReferenceRepository();
		$faker = Factory::create();
		$params = [
			'subject' => $faker->sentence,
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

	public function testUpdateRepresentativePollIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$faker = Factory::create();
		$params = [
			'subject' => $faker->sentence,
		];
		$question = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
		$this->assertSame($params['subject'], $data['subject']);
	}

	public function testPublishGroupPollReturnsErrors()
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $errors = [
            'Poll is already published',
            'Published poll amount has been reached',
        ];
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(false));
        $client->getContainer()->set($serviceId, $service);
		$question = $repository->getReference('group_question_4');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
        $this->assertSame($errors, $data['errors']['errors']);
	}

	public function testPublishRepresentativePollReturnsErrors()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        $errors = [
            'Poll is already published',
            'Published poll amount has been reached',
        ];
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(false));
        $client->getContainer()->set($serviceId, $service);
		$question = $repository->getReference('representative_question_4');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
        $this->assertSame($errors, $data['errors']['errors']);
	}

	public function testPublishGroupPollWithOneOptionReturnsErrors()
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $errors = [
            'You must specify at least two options',
        ];
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(true));
        $client->getContainer()->set($serviceId, $service);
		$question = $repository->getReference('group_question_5');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
        $this->assertSame($errors, $data['errors']['errors']);
	}

	public function testPublishRepresentativePollWithOneOptionReturnsErrors()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        $errors = [
            'You must specify at least two options',
        ];
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(true));
        $client->getContainer()->set($serviceId, $service);
		$question = $repository->getReference('representative_question_5');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
        $this->assertSame($errors, $data['errors']['errors']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testPublishGroupPollWithWrongCredentialsReturnsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference($reference);
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testPublishRepresentativePollWithWrongCredentialsReturnsException()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference('representative_question_1');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testPublishGroupPollIsOk($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures)
        )->getReferenceRepository();
        /** @var Question $question */
		$question = $repository->getReference($reference);
        $this->assertNull($question->getPublishedAt());
        $this->assertNull($question->getExpireAt());
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(true));
        $client->getContainer()->set($serviceId, $service);
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['published_at']);
		$this->assertNotNull($data['expire_at']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM activities WHERE question_id = ? AND type = ? AND expire_at IS NOT NULL', [$question->getId(), 'question']);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM hash_tags WHERE name = ?', ['#testhashtag']);
        $this->assertEquals(1, $count);
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags_questions WHERE question_id = ?', [$data['id']]);
        $this->assertCount($count, $data['cached_hash_tags']);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendPushPublishQuestion', [
            $question->getId(),
            $question->getGroup()->getOfficialName() . ' Poll',
            $question->getSubject()
        ]));
    }

	public function testPublishRepresentativePollIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var Question\Representative $question */
		$question = $repository->getReference('representative_question_1');
        $this->assertNull($question->getPublishedAt());
        $this->assertNull($question->getExpireAt());
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(true));
        $client->getContainer()->set($serviceId, $service);
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['published_at']);
		$this->assertNotNull($data['expire_at']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM activities WHERE question_id = ? AND type = ? AND expire_at IS NOT NULL', [$question->getId(), 'question']);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM hash_tags WHERE name = ?', ['#testhashtag']);
        $this->assertEquals(1, $count);
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags_questions WHERE question_id = ?', [$data['id']]);
        $this->assertCount($count, $data['cached_hash_tags']);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendPushPublishQuestion', [
            $question->getId(),
            $question->getRepresentative()->getOfficialTitle() . ' Poll',
            $question->getSubject()
        ]));
    }

	public function testPublishGroupNewsWithoutOptionsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupNewsData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
		$question = $repository->getReference('group_news_1');
        $this->assertNull($question->getPublishedAt());
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(true));
        $client->getContainer()->set($serviceId, $service);
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['published_at']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM activities WHERE question_id = ? AND type = ? AND expire_at IS NOT NULL', [$question->getId(), 'leader-news']);
        $this->assertEquals(1, $count);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendPushPublishQuestion', [
            $question->getId(),
            $question->getGroup()->getOfficialName() . ' Discussion',
            $question->getSubject()
        ]));
    }

	public function testPublishRepresentativeNewsWithoutOptionsIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeNewsData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
		$question = $repository->getReference('representative_news_1');
        $this->assertNull($question->getPublishedAt());
		$client = $this->client;
        $serviceId = 'civix_core.question_limit';
        $service = $this->getServiceMockBuilder($serviceId)
            ->setMethods(['checkLimits'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('checkLimits')->will($this->returnValue(true));
        $client->getContainer()->set($serviceId, $service);
		$client->request('PATCH', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['published_at']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM activities WHERE question_id = ? AND type = ? AND expire_at IS NOT NULL', [$question->getId(), 'leader-news']);
        $this->assertEquals(1, $count);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendPushPublishQuestion', [
            $question->getId(),
            $question->getGroup()->getOfficialTitle() . ' Discussion',
            $question->getSubject()
        ]));
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testDeleteGroupPollWithWrongCredentialsReturnsException($user, $reference)
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

	public function testDeleteRepresentativePollWithWrongCredentialsReturnsException()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference('representative_question_1');
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testDeleteGroupPollIsOk($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures)
        )->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}

	public function testDeleteRepresentativePollIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testCreateGroupPollOptionReturnsErrors($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures)
        )->getReferenceRepository();
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

	public function testCreateRepresentativePollOptionReturnsErrors()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('representative_question_1');
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT.'/'.$question->getId().'/options', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		$this->assertEquals(['This value should not be blank.'], $children['value']['errors']);
	}

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testCreateGroupPollOptionIsOk($fixtures, $user, $reference)
	{
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures)
        )->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
        $params = [
            'value' => 'some_value',
            'payment_amount' => 100,
            'is_user_amount' => false,
        ];
        $client->request('POST', self::API_ENDPOINT.'/'.$question->getId().'/options', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['value'], $data['value']);
		$this->assertSame($params['payment_amount'], $data['payment_amount']);
		$this->assertSame($params['is_user_amount'], $data['is_user_amount']);
	}

	public function testCreateRepresentativePollOptionIsOk()
	{
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference('representative_question_1');
		$client = $this->client;
        $params = [
            'value' => 'some_value',
            'payment_amount' => 100,
            'is_user_amount' => false,
        ];
        $client->request('POST', self::API_ENDPOINT.'/'.$question->getId().'/options', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['value'], $data['value']);
		$this->assertSame($params['payment_amount'], $data['payment_amount']);
		$this->assertSame($params['is_user_amount'], $data['is_user_amount']);
	}

    public function testAddGroupAnswerWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('group_question_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testAddRepresentativeAnswerWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('representative_question_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testAddGroupAnswerToAnsweredQuestionThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionAnswerData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('group_question_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testAddRepresentativeAnswerToAnsweredQuestionThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionAnswerData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('representative_question_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testAddGroupAnswerReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('group_question_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['comment' => str_repeat('x', 501)]));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $children = $data['errors']['children'];
        $this->assertEquals(['This value is too long. It should have 500 characters or less.'], $children['comment']['errors']);
    }

    public function testAddRepresentativeAnswerReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('representative_question_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['comment' => str_repeat('x', 501)]));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $children = $data['errors']['children'];
        $this->assertEquals(['This value is too long. It should have 500 characters or less.'], $children['comment']['errors']);
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForGetRequest
     */
    public function testAddGroupAnswerIsOk($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures(
            array_merge([LoadGroupQuestionData::class], $fixtures)
        )->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference($reference);
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $stripe = $this->getMockBuilder(Stripe::class)
            ->setMethods(['chargeToPaymentRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $stripe->expects($this->never())->method('chargeToPaymentRequest');
        $client->getContainer()->set('civix_core.stripe', $stripe);
        $faker = Factory::create();
        $params = [
            'comment' => $faker->sentence,
            'privacy' => 'private',
            'payment_amount' => 1234,
        ];
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($option->getId(), $data['option_id']);
        $this->assertEquals($params['comment'], $data['comment']);
        $this->assertEquals($params['payment_amount'], $data['payment_amount']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $amount = $conn->fetchColumn('SELECT crowdfunding_pledged_amount FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(0, $amount);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_comments WHERE question_id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT answers_count FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $tester = new SocialActivityTester($em);
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_OWN_POLL_ANSWERED, $question->getUser()->getId());
    }

    public function testAddRepresentativeAnswerIsOk()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('representative_question_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $stripe = $this->getMockBuilder(Stripe::class)
            ->setMethods(['chargeToPaymentRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $stripe->expects($this->never())->method('chargeToPaymentRequest');
        $client->getContainer()->set('civix_core.stripe', $stripe);
        $faker = Factory::create();
        $params = [
            'comment' => $faker->sentence,
            'privacy' => 'private',
            'payment_amount' => 1234,
        ];
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($option->getId(), $data['option_id']);
        $this->assertEquals($params['comment'], $data['comment']);
        $this->assertEquals($params['payment_amount'], $data['payment_amount']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $amount = $conn->fetchColumn('SELECT crowdfunding_pledged_amount FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(0, $amount);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_comments WHERE question_id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT answers_count FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
    }

    public function testAddGroupPaymentAnswerToCrowdfundingRequestIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupPaymentRequestData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('group_payment_request_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(0);
        $client = $this->client;
        $stripe = $this->getMockBuilder(Stripe::class)
            ->setMethods(['chargeToPaymentRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $stripe->expects($this->never())->method('chargeToPaymentRequest');
        $client->getContainer()->set('civix_core.stripe', $stripe);
        $faker = Factory::create();
        $params = [
            'comment' => $faker->sentence,
            'privacy' => 'private',
            'payment_amount' => 1234,
        ];
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($option->getId(), $data['option_id']);
        $this->assertEquals($params['comment'], $data['comment']);
        $this->assertEquals($params['payment_amount'], $data['payment_amount']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $amount = $conn->fetchColumn('SELECT crowdfunding_pledged_amount FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1234, $amount);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_comments WHERE question_id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT answers_count FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $tester = new SocialActivityTester($em);
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_OWN_POLL_ANSWERED, $question->getUser()->getId());
    }

    public function testAddRepresentativePaymentAnswerToCrowdfundingRequestIsOk()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativePaymentRequestData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('representative_payment_request_1');
        /** @var Option $option */
        $option = $question->getOptions()->get(0);
        $client = $this->client;
        $stripe = $this->getMockBuilder(Stripe::class)
            ->setMethods(['chargeToPaymentRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $stripe->expects($this->never())->method('chargeToPaymentRequest');
        $client->getContainer()->set('civix_core.stripe', $stripe);
        $faker = Factory::create();
        $params = [
            'comment' => $faker->sentence,
            'privacy' => 'private',
            'payment_amount' => 1234,
        ];
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($option->getId(), $data['option_id']);
        $this->assertEquals($params['comment'], $data['comment']);
        $this->assertEquals($params['payment_amount'], $data['payment_amount']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $amount = $conn->fetchColumn('SELECT crowdfunding_pledged_amount FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1234, $amount);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_comments WHERE question_id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT answers_count FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
    }

    public function testAddGroupPaymentAnswerToNotCrowdfundingRequestIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupPaymentRequestData::class,
            LoadStripeCustomerUserData::class,
            LoadAccountGroupData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('group_payment_request_2');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $stripe = $this->getMockBuilder(Stripe::class)
            ->setMethods(['chargeToPaymentRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $stripe->expects($this->once())
            ->method('chargeToPaymentRequest')
            ->with($this->callback(function(Charge $charge) {
                $this->assertEquals(50000, $charge->getAmount());

                return true;
            }))
            ->willReturn($this->getCharge());
        $client->getContainer()->set('civix_core.stripe', $stripe);
        $faker = Factory::create();
        $params = [
            'comment' => $faker->sentence,
            'privacy' => 'private',
        ];
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($option->getId(), $data['option_id']);
        $this->assertEquals($params['comment'], $data['comment']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $amount = $conn->fetchColumn('SELECT crowdfunding_pledged_amount FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(0, $amount);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_comments WHERE question_id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT answers_count FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $tester = new SocialActivityTester($em);
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_OWN_POLL_ANSWERED, $question->getUser()->getId());
    }

    public function testAddRepresentativePaymentAnswerToNotCrowdfundingRequestIsOk()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativePaymentRequestData::class,
            LoadStripeCustomerUserData::class,
            LoadAccountRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Question $question */
        $question = $repository->getReference('representative_payment_request_2');
        /** @var Option $option */
        $option = $question->getOptions()->get(1);
        $client = $this->client;
        $stripe = $this->getMockBuilder(Stripe::class)
            ->setMethods(['chargeToPaymentRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $stripe->expects($this->once())
            ->method('chargeToPaymentRequest')
            ->with($this->callback(function(Charge $charge) {
                $this->assertEquals(50000, $charge->getAmount());

                return true;
            }))
            ->willReturn($this->getCharge());
        $client->getContainer()->set('civix_core.stripe', $stripe);
        $faker = Factory::create();
        $params = [
            'comment' => $faker->sentence,
            'privacy' => 'private',
        ];
        $client->request('PUT', self::API_ENDPOINT.'/'.$question->getId().'/answers/'.$option->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($option->getId(), $data['option_id']);
        $this->assertEquals($params['comment'], $data['comment']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $amount = $conn->fetchColumn('SELECT crowdfunding_pledged_amount FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(0, $amount);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_comments WHERE question_id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
        $count = $conn->fetchColumn('SELECT answers_count FROM poll_questions WHERE id = ?', [$question->getId()]);
        $this->assertEquals(1, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForGetResponsesRequest
     */
    public function testGetPollResponsesWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionAnswerData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/responses', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetRepresentativePollResponsesReturns404()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeQuestionAnswerData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('representative_question_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/responses', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }

    public function testGetPollResponsesIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionAnswerData::class,
            LoadUserFollowerData::class,
            LoadGroupManagerData::class,
            LoadFieldValueData::class,
            LoadGroupRepresentativesData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('group_question_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/responses', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertSame("test-field-value-2", $data[0]['test-group-field']);
        $this->assertSame("test-field-value-3", $data[1]['test-group-field']);
        $this->assertNull($data[2]['test-group-field']);
        foreach ([$user2, $user3, $user4] as $k => $user) {
            /** @var User $user */
            $this->assertEquals($user->getAddress(), $data[$k]['address']);
            $this->assertSame($user->getEmail(), $data[$k]['email']);
            $this->assertSame($user->getPhone(), $data[$k]['phone']);
            $this->assertSame($user->getCity(), $data[$k]['city']);
            $this->assertSame($user->getState(), $data[$k]['state']);
            $this->assertSame($user->getCountry(), $data[$k]['country']);
            $this->assertSame($user->getZip(), $data[$k]['zip_code']);
            $this->assertSame($user->getBio(), $data[$k]['bio']);
            $this->assertArrayHasKey('karma', $data[$k]);
            $this->assertSame("1", $data[$k]['followers']);
            $this->assertEquals('1', $data[$k]['facebook']);
            $this->assertNotEmpty($data[$k]['comment']);
            $this->assertThat($data[$k]['choice'], $this->logicalOr('val 0', 'val 1'));
            if ($user === $user3) { // private
                $this->assertNull($data[$k]['name']);
            } else {
                $this->assertSame($user->getFullName(), $data[$k]['name']);
            }
        }
    }

    public function testGetPollResponsesCsvIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionAnswerData::class,
            LoadUserFollowerData::class,
            LoadGroupManagerData::class,
            LoadFieldValueData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('group_question_1');
        $answer1 = $repository->getReference('question_answer_1');
        $answer2 = $repository->getReference('question_answer_2');
        $answer3 = $repository->getReference('question_answer_3');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/responses', [], [], [
            'HTTP_ACCEPT' => 'text/csv',
            'HTTP_Authorization'=>'Bearer type="user" token="user1"',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(
            "name,address,city,state,country,zip_code,email,phone,bio,slogan,facebook,followers,karma," .
            "test-group-field,\"\"\"field1`\",\"\"\"field2`\",\"\"\"field3`\",\"\"\"field4`\",choice,comment\n" .
            "\"user 2\",,,,US,,{$user2->getEmail()},{$user2->getPhone()},,,1,1,0,test-field-value-2,,,,,\"{$answer1->getOption()->getValue()}\",\"{$answer1->getComment()}\"\n" .
            ",,,,US,,{$user3->getEmail()},{$user3->getPhone()},,,1,1,0,test-field-value-3,,,,,\"{$answer2->getOption()->getValue()}\",\"{$answer2->getComment()}\"\n" .
            "\"user 4\",,,,US,,{$user4->getEmail()},{$user4->getPhone()},,,1,1,0,,,,,,\"{$answer3->getOption()->getValue()}\",\"{$answer3->getComment()}\"\n",
            $response->getContent()
        );
    }

    public function testGetGroupMembersCsvLinkIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('group_question_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$question->getId().'/responses-link', [], [], ['HTTP_AUTHORIZATION' => 'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertRegExp('~/api-public/files/\S{8}-\S{4}-\S{4}-\S{4}-\S{12}~', $data['url']);
        $this->assertLessThanOrEqual(new \DateTime('+2 minutes'), new \DateTime($data['expired_at']));
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $file = $conn->fetchAssoc('SELECT * FROM temp_files LIMIT 1');
        $this->assertRegExp('~\S{8}-\S{4}-\S{4}-\S{4}-\S{12}~', $file['id']);
        $this->assertEquals('a:0:{}', $file['body']);
        $this->assertEquals(null, $file['filename']);
        $this->assertEquals('text/csv', $file['mimeType']);
        $this->assertLessThanOrEqual(new \DateTime('+2 minutes'), new \DateTime($file['expiredAt']));
    }

    public function getValidPollCredentialsForGetRequest()
    {
        return [
            'owner' => [[], 'user1', 'group_question_1'],
            'manager' => [[LoadGroupManagerData::class], 'user2', 'group_question_1'],
            'member' => [[LoadUserGroupData::class], 'user4', 'group_question_1'],
            'representative' => [[LoadGroupRepresentativesData::class], 'user3', 'group_question_1'],
        ];
    }

    public function getInvalidPollCredentialsForGetResponsesRequest()
    {
        return [
            'manager' => ['user2', 'group_question_1'],
            'member' => ['user4', 'group_question_1'],
            'outlier' => ['user1', 'group_question_2'],
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
            'owner' => [[], 'user1', 'group_question_1'],
            'manager' => [[LoadGroupManagerData::class], 'user2', 'group_question_1'],
            'representative' => [[LoadGroupRepresentativesData::class], 'user3', 'group_question_1'],
        ];
    }

    /**
     * @return \Stripe\Charge
     */
    public function getCharge()
    {
        $charge = new \Stripe\Charge('ch_0000');
        $charge->status = 'success';
        $charge->amount = 50000;
        $charge->currency = 'usd';
        $charge->application_fee = 2550;
        $charge->receipt_number = 'rec_000';
        $charge->created = time();

        return $charge;
    }
}

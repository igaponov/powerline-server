<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Group;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupNewsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupPaymentRequestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
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

	public function testGetPollAnswersFollowingIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadQuestionAnswerData::class,
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

	public function testGetPollAnswersFollowingOutsideIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadQuestionAnswerData::class,
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

	public function testPublishPollReturnsErrors()
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

	public function testPublishPollWithOneOptionReturnsErrors()
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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testPublishPollWithWrongCredentialsReturnsException($user, $reference)
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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testPublishPollIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
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
        $conn = $client->getContainer()->get('database_connection');
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

	public function testPublishNewsWithoutOptionsIsOk()
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
        $conn = $client->getContainer()->get('database_connection');
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

    public function testAddAnswerWithWrongCredentialsThrowsException()
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

    public function testAddAnswerToAnsweredQuestionThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadQuestionAnswerData::class,
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

    public function testAddAnswerReturnsErrors()
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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForGetRequest
     */
    public function testAddAnswerIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
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
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
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

    public function testAddPaymentAnswerToCrowdfundingRequestIsOk()
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
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
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

    public function testAddPaymentAnswerToNotCrowdfundingRequestIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupPaymentRequestData::class,
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
            ->with($this->callback(function(Answer $answer) {
                $this->assertEquals(500, $answer->getCurrentPaymentAmount());

                return true;
            }));
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
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
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

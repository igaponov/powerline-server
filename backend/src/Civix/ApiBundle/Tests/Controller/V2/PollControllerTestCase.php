<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class PollControllerTestCase extends WebTestCase
{
	/**
	 * @var Client
	 */
	protected $client = null;

    abstract protected function getApiEndpoint();

	public function setUp()
	{
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		$this->client = NULL;
        parent::tearDown();
    }

	public function getPollsWithWrongCredentialsThrowsException(LeaderContentRootInterface $root)
	{
		$client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param LeaderContentRootInterface $root
     * @param $user
     * @dataProvider getValidPollCredentialsForGetRequest
     */
	public function getPollsIsOk(LeaderContentRootInterface $root, $user)
	{
		$client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

    /**
     * @param LeaderContentRootInterface $root
     * @param $params
     * @dataProvider getFilters
     */
	public function getFilteredPollsIsOk(LeaderContentRootInterface $root, $params)
	{
		$client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, $params, [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
	}

    public function getFilters()
    {
        return [
            'published' => [['filter' => 'published']],
            'unpublished' => [['filter' => 'unpublished']],
        ];
	}

    /**
     * @param LeaderContentRootInterface $root
     * @param $user
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function createPollWithWrongCredentialsThrowsException(LeaderContentRootInterface $root, $user)
	{
		$client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param LeaderContentRootInterface $root
     * @param array $params
     * @param array $errors
     * @dataProvider getInvalidParams
     */
	public function createPollReturnsErrors(LeaderContentRootInterface $root, $params, $errors)
	{
		$client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
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
     * @param LeaderContentRootInterface $root
     * @param $params
     * @dataProvider getValidParams
     */
	public function createPollIsOk(LeaderContentRootInterface $root, $params)
	{
        $client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($params as $param => $value) {
			if (isset($data[$param])) {
				$this->assertSame($value, $data[$param]);
			}
		}
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        // check author subscription
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_subscribers WHERE question_id = ?', [$data['id']]);
        $this->assertEquals(1, $count);
	}

	public function createPaymentRequestWithoutStripeAccountThrowsException(LeaderContentRootInterface $root)
	{
        $faker = Factory::create();
        $params = [
            'type' => 'payment_request',
            'subject' => $faker->sentence,
            'title' => $faker->sentence,
            'is_allow_outsiders' => $faker->boolean(),
            'is_crowdfunding' => false,
            'crowdfunding_goal_amount' => $faker->randomDigit,
            'crowdfunding_deadline' => date('D, d M Y H:i:s', time() + 500000),
            'is_crowdfunding_completed' => $faker->boolean(),
            'crowdfunding_pledged_amount' => $faker->randomDigit,
        ];
        $client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(500, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertEquals('You must have a Stripe account to create a payment request.', $data['message']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        // check author subscription
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM poll_questions WHERE title = ?', [$params['title']]);
        $this->assertEquals(0, $count);
	}

    /**
     * @param LeaderContentRootInterface $root
     * @param $user
     * @param array $params
     * @dataProvider getValidPollCredentialsForUpdateRequest
     * @return mixed
     */
	public function createPollWithCorrectCredentials(LeaderContentRootInterface $root, $user, $params = [])
	{
        $client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $params = array_merge(
            $params, [
                'type' => 'news',
                'subject' => 'subj',
            ]
        );
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['user']);
		foreach ($params as $param => $value) {
			if (isset($data[$param])) {
				$this->assertSame($value, $data[$param]);
			}
		}
		return $data;
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
					'type' => 'news',
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
			'news' => [
				[
					'type' => 'news',
					'subject' => $faker->sentence,
				]
			],
			'event' => [
				[
					'type' => 'event',
					'subject' => $faker->sentence,
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
					'title' => $faker->sentence,
					'is_allow_outsiders' => $faker->boolean(),
					'is_crowdfunding' => false,
					'crowdfunding_goal_amount' => $faker->randomDigit,
					'crowdfunding_deadline' => date('D, d M Y H:i:s', time() + 500000),
					'is_crowdfunding_completed' => $faker->boolean(),
					'crowdfunding_pledged_amount' => $faker->randomDigit,
				]
			],
			'crowdfunding_request' => [
				[
					'type' => 'payment_request',
					'subject' => $faker->sentence,
					'title' => $faker->sentence,
					'is_allow_outsiders' => $faker->boolean(),
					'is_crowdfunding' => true,
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
					'is_outsiders_sign' => $faker->boolean(),
					'petition_title' => $faker->sentence,
					'petition_body' => $faker->text,
				]
			],
		];
	}
}
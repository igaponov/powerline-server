<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Faker\Factory;
use Symfony\Component\BrowserKit\Client;

class PollControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/polls';
	
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
		$this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

	public function testGetPollsWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForGetRequest
     */
	public function testGetPollsIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
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
     * @param $params
     * @dataProvider getFilters
     */
	public function testGetFilteredPollsIsOk($params)
	{
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
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
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testCreatePollWithWrongCredentialsThrowsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreatePollReturnsErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
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
	 * @param $params
	 * @dataProvider getValidParams
	 */
	public function testCreatePollIsOk($params)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
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
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testCreatePollWithCorrectCredentials($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $params = [
            'type' => 'group',
            'subject' => 'subj',
            'report_recipient_group' => 'group',
        ];
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

    public function getInvalidPollCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user1', 'group_3'],
        ];
    }

    public function getValidPollCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user3', 'group_3'],
            'manager' => ['user2', 'group_3'],
        ];
    }

    public function getValidPollCredentialsForGetRequest()
    {
        return [
            'owner' => ['user3', 'group_3'],
            'manager' => ['user2', 'group_3'],
            'member' => ['user4', 'group_3'],
        ];
    }
}

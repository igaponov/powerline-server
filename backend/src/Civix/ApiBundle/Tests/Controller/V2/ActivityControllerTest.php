<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityRelationsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSubscriberData;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Client;

class ActivityControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/activities';
	
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

	public function testGetActivitiesNotAuthorized()
	{
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT);
		$response = $client->getResponse();
		$this->assertEquals(401, $response->getStatusCode(), $response->getContent());
	}

	public function testGetActivitiesIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadActivityRelationsData::class,
            LoadUserPetitionSubscriberData::class,
            LoadPostSubscriberData::class,
            LoadPollSubscriberData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(8, $data['totalItems']);
		$this->assertCount(8, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertNotEmpty($item['user']);
            if ($item['entity']['type'] == 'user-petition') {
                $this->assertTrue($item['user_petition']['is_subscribed']);
            } elseif ($item['entity']['type'] == 'post') {
                $this->assertTrue($item['post']['is_subscribed']);
            } elseif ($item['entity']['type'] == 'question') {
                $this->assertTrue($item['poll']['is_subscribed']);
            } elseif ($item['entity']['type'] == 'petition') {
                $this->assertFalse($item['poll']['is_subscribed']);
            }
            if ($item['entity']['type'] == 'micro-petition') {
                $this->assertArrayHasKey('comments_count', $item);
                $this->assertArrayHasKey('answers', $item);
                $this->assertInternalType('array', $item['answers']);
            }
            if ($item['expire_at'] && strtotime($item['expire_at']) < time()) {
                $this->assertSame('expired', $item['zone']);
            } else {
                $this->assertSame('prioritized', $item['zone']);
            }
            $this->assertArrayHasKey('description_html', $item);
        }
	}

	public function testGetActivitiesIsEmpty()
	{
        $this->loadFixtures([
            LoadUserData::class,
        ]);
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

	public function testGetActivitiesByFollowingIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
            LoadUserFollowerData::class,
            LoadActivityData::class,
        ])->getReferenceRepository();
		/** @var User $user */
		$user = $repository->getReference('user_2');
        $following = $repository->getReference('user_1');
        $group = $repository->getReference('group_1');
		$client = $this->client;
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $conn->insert('users_groups', ['user_id' => $following->getId(), 'group_id' => $group->getId(), 'status' => UserGroup::STATUS_ACTIVE, 'created_at' => date('Y-m-d H:i:s')]);
		$client->request('GET', self::API_ENDPOINT, ['following' => $user->getId()], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
		$current = reset($data['payload']);
		while ($next = next($data['payload'])) {
            $this->assertSame('prioritized', $next['zone']);
            $this->assertLessThanOrEqual(
				strtotime($current['sent_at']),
				strtotime($next['sent_at'])
			);
			$current = $next;
		}
	}

	public function testGetActivitiesByFollowingIsEmpty()
	{
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadActivityData::class,
        ])->getReferenceRepository();
		/** @var User $user */
		$user = $repository->getReference('user_2');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['following' => $user->getId()], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

	public function testPatchActivitiesWithEmptyArray()
	{
		$data = ['activities' => []];
		$client = $this->client;
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertContains("This value should not be blank.", $data['errors']['errors']);
	}

	public function testPatchActivitiesWithEmptyId()
	{
		$data = ['activities' => [['id' => '']]];
		$client = $this->client;
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertContains(
			"This value should not be blank.",
			$data['errors']['children']['activities']['children'][0]['children']['id']['errors']
		);
		$this->assertContains(
			"This value should not be blank.",
			$data['errors']['children']['activities']['children'][0]['children']['read']['errors']
		);
	}

	public function testPatchActivities()
	{
        $repository = $this->loadFixtures([
            LoadActivityData::class,
        ])->getReferenceRepository();
		$leaderNews = $repository->getReference('activity_leader_news');
		$paymentRequest = $repository->getReference('activity_crowdfunding_payment_request');
		$question = $repository->getReference('activity_question');
		$params = [
			'activities' => [
				['id' => $leaderNews->getId(), 'read' => true],
				['id' => $paymentRequest->getId(), 'read' => true],
				['id' => $question->getId(), 'read' => true],
			],
		];
		$client = $this->client;
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertCount(3, $data);
		foreach ($data as $activity) {
			$this->assertThat(
				$activity['id'],
				$this->logicalOr($leaderNews->getId(), $paymentRequest->getId(), $question->getId())
			);
			$this->assertTrue($activity['read']);
		}
		/** @var Connection $conn */
		$conn = $client->getContainer()->get('database_connection');
		$ids = $conn->fetchAll('
			SELECT activity_id FROM activities_read 
			WHERE activity_id IN (?,?,?)
			ORDER BY activity_id', [
			$leaderNews->getId(), $paymentRequest->getId(), $question->getId()
		]);
		$arr = [$leaderNews->getId(), $paymentRequest->getId(), $question->getId()];
		sort($arr);
		$this->assertEquals($arr, array_map('intval', array_column($ids, 'activity_id')));
        // the same request does nothing
        $client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals($data, json_decode($response->getContent(), true));
    }
}

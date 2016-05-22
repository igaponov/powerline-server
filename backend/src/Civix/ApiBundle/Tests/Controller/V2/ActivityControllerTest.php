<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityData;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowData;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
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
			LoadUserFollowData::class,
			LoadActivityData::class,
		])->getReferenceRepository();

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
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
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(7, $data['totalItems']);
		$this->assertCount(7, $data['payload']);
		$current = reset($data['payload']);
		while ($next = next($data['payload'])) {
			$this->assertLessThanOrEqual(
				strtotime($current['sent_at']), 
				strtotime($next['sent_at'])
			);
			$current = $next;
		}
	}

	public function testGetActivitiesIsEmpty()
	{
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest3"']);
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
		/** @var User $user */
		$user = $this->repository->getReference('userfollowtest1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['following' => $user->getId()], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
		$current = reset($data['payload']);
		while ($next = next($data['payload'])) {
			$this->assertLessThanOrEqual(
				strtotime($current['sent_at']),
				strtotime($next['sent_at'])
			);
			$current = $next;
		}
	}

	public function testGetActivitiesByFollowingIsEmpty()
	{
		/** @var User $user */
		$user = $this->repository->getReference('userfollowtest2');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['following' => $user->getId()], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
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
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertContains("This value should not be blank.", $data['errors']['errors']);
	}

	public function testPatchActivitiesWithEmptyId()
	{
		$data = ['activities' => [['id' => '']]];
		$client = $this->client;
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], json_encode($data));
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
		$leaderNews = $this->repository->getReference('activity_leader_news');
		$paymentRequest = $this->repository->getReference('activity_crowdfunding_payment_request');
		$question = $this->repository->getReference('activity_question');
		$data = [
			'activities' => [
				['id' => $leaderNews->getId(), 'read' => true],
				['id' => $paymentRequest->getId(), 'read' => true],
				['id' => $question->getId(), 'read' => true], // wrong id
			],
		];
		$client = $this->client;
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertCount(2, $data);
		foreach ($data as $activity) {
			$this->assertThat(
				$activity['id'],
				$this->logicalOr($leaderNews->getId(), $paymentRequest->getId())
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
		$arr = [$leaderNews->getId(), $paymentRequest->getId()];
		sort($arr);
		$this->assertEquals($arr, array_map('intval', array_column($ids, 'activity_id')));
	}
}
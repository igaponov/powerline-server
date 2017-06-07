<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityReadAuthorData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityRelationsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadEducationalContextData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSubscriberData;
use Doctrine\DBAL\Connection;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
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
	private $client;

	public function setUp(): void
    {
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown(): void
    {
		$this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

	public function testGetActivitiesNotAuthorized(): void
    {
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT);
		$response = $client->getResponse();
		$this->assertEquals(401, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @QueryCount(10)
     * @todo cache user's newsfeed
     */
	public function testGetActivitiesIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadActivityRelationsData::class,
            LoadUserPetitionSubscriberData::class,
            LoadPostSubscriberData::class,
            LoadPollSubscriberData::class,
            LoadEducationalContextData::class,
            LoadActivityReadAuthorData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = $this->deserializePagination($response->getContent(), 1, 8, 20);
        $activities = array_map(function($name) use ($repository) {
            return $repository->getReference($name)->getId();
        }, [
            'activity_leader_news',
            'activity_crowdfunding_payment_request',
            'activity_user_petition',
            'activity_post',
            'activity_petition',
            'activity_question',
            'activity_leader_event',
            'activity_payment_request',
        ]);
		foreach ($data->getItems() as $key => $item) {
            $this->assertActivity($item, $activities);
        }
	}

	public function testGetActivitiesByFollowedIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadActivityRelationsData::class,
            LoadUserPetitionSubscriberData::class,
            LoadPostSubscriberData::class,
            LoadPollSubscriberData::class,
            LoadEducationalContextData::class,
            LoadActivityReadAuthorData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['followed' => true], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = $this->deserializePagination($response->getContent(), 1, 4, 20);
        $activities = array_map(function($name) use ($repository) {
            return $repository->getReference($name)->getId();
        }, [
            'activity_petition',
            'activity_question',
            'activity_leader_event',
            'activity_payment_request',
        ]);
        foreach ($data->getItems() as $item) {
            $this->assertActivity($item, $activities);
        }
	}

	public function testGetActivitiesByNonFollowedIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadActivityRelationsData::class,
            LoadUserPetitionSubscriberData::class,
            LoadPostSubscriberData::class,
            LoadPollSubscriberData::class,
            LoadEducationalContextData::class,
            LoadActivityReadAuthorData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['non_followed' => true], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = $this->deserializePagination($response->getContent(), 1, 4, 20);
        $activities = array_map(function($name) use ($repository) {
            return $repository->getReference($name)->getId();
        }, [
            'activity_leader_news',
            'activity_crowdfunding_payment_request',
            'activity_user_petition',
            'activity_post',
        ]);
        foreach ($data->getItems() as $item) {
            $this->assertActivity($item, $activities);
        }
	}

    /**
     * @QueryCount(7)
     */
	public function testGetActivitiesFilteredByGroupIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadActivityData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $group = $repository->getReference('group_1');
        $client->request('GET', self::API_ENDPOINT, ['group' => $group->getId()], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(8, $data['totalItems']);
		$this->assertCount(8, $data['payload']);
	}

    /**
     * @QueryCount(7)
     */
	public function testGetActivitiesFilteredByGroupIsEmpty(): void
    {
        $repository = $this->loadFixtures([
            LoadActivityData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $group = $repository->getReference('group_4');
        $client->request('GET', self::API_ENDPOINT, ['group' => $group->getId()], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

    /**
     * @QueryCount(7)
     */
	public function testGetActivitiesIsEmpty(): void
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user4']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

    /**
     * @QueryCount(7)
     */
	public function testGetActivitiesByFollowingIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadUserFollowerData::class,
            LoadActivityData::class,
        ])->getReferenceRepository();
		/** @var User $user */
		$user = $repository->getReference('user_2');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['following' => $user->getId()], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = $this->deserializePagination($response->getContent(), 1, 2, 20);
		foreach ($data->getItems() as $next) {
		    if ($next['entity']['type'] === 'question') {
                $this->assertSame('expired', $next['zone']);
            } else {
                $this->assertSame('prioritized', $next['zone']);
            }
		}
	}

    /**
     * @QueryCount(7)
     */
	public function testGetActivitiesByFollowingIsEmpty(): void
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadActivityData::class,
        ])->getReferenceRepository();
		/** @var User $user */
		$user = $repository->getReference('user_2');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['following' => $user->getId()], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

    /**
     * @QueryCount(7)
     */
	public function testGetActivitiesByAnotherUserIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadActivityData::class,
            LoadUserGroupData::class,
            LoadUserGroupOwnerData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
		/** @var User $user */
		$user = $repository->getReference('user_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, ['user' => $user->getId(), 'type' => ['post', 'petition']], [], ['HTTP_Authorization'=>'Bearer user2']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = $this->deserializePagination($response->getContent(), 1, 2, 20);
        foreach ($data->getItems() as $item) {
            $this->assertThat($item['entity']['type'], $this->logicalOr('post', 'user-petition'));
		}
	}

	public function testPatchActivitiesWithEmptyArray(): void
    {
		$data = ['activities' => []];
		$client = $this->client;
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertContains('This value should not be blank.', $data['errors']['errors']);
	}

	public function testPatchActivitiesWithEmptyId(): void
    {
		$data = ['activities' => [['id' => '']]];
		$client = $this->client;
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($data));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertContains(
			'This value should not be blank.',
			$data['errors']['children']['activities']['children'][0]['children']['id']['errors']
		);
		$this->assertContains(
			'This value should not be blank.',
			$data['errors']['children']['activities']['children'][0]['children']['read']['errors']
		);
	}

    /**
     * @QueryCount(7)
     */
	public function testPatchActivities(): void
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
		$client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertCount(3, $data);
        /** @var array $data */
        foreach ($data as $activity) {
			$this->assertThat(
				$activity['id'],
				$this->logicalOr($leaderNews->getId(), $paymentRequest->getId(), $question->getId())
			);
			$this->assertTrue($activity['read']);
		}
		/** @var Connection $conn */
		$conn = $client->getContainer()->get('doctrine')->getConnection();
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
        $client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals($data, json_decode($response->getContent(), true));
    }

    /**
     * @param $item
     * @param $activities
     */
    private function assertActivity($item, $activities): void
    {
        $this->assertThat($item['id'], $this->logicalOr(...$activities));
        $this->assertNotEmpty($item['user']);
        $this->assertArrayHasKey('group', $item);
        if ($item['entity']['type'] === 'user-petition') {
            $this->assertTrue($item['user_petition']['is_subscribed']);
        } elseif ($item['entity']['type'] === 'post') {
            $this->assertTrue($item['post']['is_subscribed']);
        } elseif ($item['entity']['type'] === 'question') {
            $this->assertTrue($item['poll']['is_subscribed']);
            $this->assertArrayHasKey('educational_context', $item['poll']);
            /** @var array $educationalContexts */
            $educationalContexts = $item['poll']['educational_context'];
            $this->assertCount(2, $educationalContexts);
            foreach ($educationalContexts as $educationalContext) {
                if ($educationalContext['type'] !== EducationalContext::TEXT_TYPE) {
                    $this->assertNotEmpty($educationalContext['preview']);
                }
            }
        } elseif ($item['entity']['type'] === 'petition') {
            $this->assertNotEmpty($item['group']['avatar_file_path']);
            $this->assertFalse($item['poll']['is_subscribed']);
        }
        if ($item['entity']['type'] === 'micro-petition') {
            $this->assertArrayHasKey('comments_count', $item);
            $this->assertArrayHasKey('answers', $item);
            $this->assertInternalType('array', $item['answers']);
        }
        if ($item['expire_at'] && strtotime($item['expire_at']) < time()) {
            $this->assertSame('expired', $item['zone']);
        } elseif (in_array($item['entity']['type'], ['user-petition', 'post'], true)) {
            $this->assertSame('non_prioritized', $item['zone']);
        } else {
            $this->assertSame('prioritized', $item['zone']);
        }
        $this->assertArrayHasKey('description_html', $item);
    }
}

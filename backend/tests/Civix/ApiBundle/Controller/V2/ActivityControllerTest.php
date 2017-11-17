<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\User;
use Doctrine\DBAL\Connection;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Tests\Civix\ApiBundle\Controller\ActivityControllerTestCase;

class ActivityControllerTest extends ActivityControllerTestCase
{
	const API_ENDPOINT = '/api/v2/activities';

    /**
     * @todo cache user's newsfeed
     *
     * @param array $params
     * @param array $references
     * @param int $queryCount
     * @dataProvider getSets
     * @throws \Doctrine\Common\DataFixtures\OutOfBoundsException
     */
	public function testGetActivitiesIsOk(array $params, array $references, int $queryCount): void
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $client = $this->client;
        $client->enableProfiler();
        $client->request('GET', self::API_ENDPOINT, $params, [], ['HTTP_Authorization'=>'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = $this->deserializePagination($response->getContent(), 1, count($references),20);
        foreach ($data->getItems() as $key => $item) {
            if ($item['owner']['type'] === 'user') {
                $this->assertNotContains('src', $item['owner']['avatar_file_path']);
            } elseif ($item['owner']['type'] === 'group') {
                $this->assertContains('src', $item['owner']['avatar_file_path']);
            } elseif ($item['owner']['type'] === 'representative') {
                $this->assertContains('representative', $item['owner']['avatar_file_path']);
            } else {
                $this->fail('Unexpected owner data type '.$item['owner']['type']);
            }
            /** @var Activity $activity */
            $activity = $repository->getReference($references[$key]);
            if ($item['group']['official_name'] === 'group1') {
                $this->assertSame($item['group']['user_role'], 'owner');
            } else {
                $this->assertNull($item['group']['user_role']);
            }
            if (in_array($item['user']['username'], ['user2', 'user4'], true)) {
                $this->assertSame($item['user']['follow_status'], 'active');
            } elseif ($item['user']['username'] === 'user3') {
                $this->assertSame($item['user']['follow_status'], 'pending');
            } else {
                $this->assertNull($item['user']['follow_status']);
            }
            $this->assertActivity($item, $activity);
        }
        /** @var DoctrineDataCollector $dataCollector */
        $dataCollector = $client->getProfile()->getCollector('db');
        $this->assertSame($queryCount, $dataCollector->getQueryCount());
	}

    public function getSets(): array
    {
        return [
            'default' => [
                [],
                [
                    'activity_leader_news',
                    'activity_payment_request',
                    'activity_petition',
                    'activity_user_petition',
                    'activity_post',
                    'activity_crowdfunding_payment_request',
                    'activity_question',
                    'activity_leader_event',
                ],
                12
            ],
            'followed' => [
                ['followed' => true],
                [
                    'activity_payment_request',
                    'activity_petition',
                    'activity_petition3',
                    'activity_question',
                    'activity_leader_event',
                ],
                12
            ],
            'non-followed' => [
                ['non_followed' => true],
                [
                    'activity_leader_news',
                    'activity_user_petition',
                    'activity_post',
                    'activity_crowdfunding_payment_request',
                ],
                13
            ]
        ];
	}

    /**
     * @QueryCount(6)
     */
	public function testGetActivitiesFilteredByGroupIsOk(): void
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
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
     * @QueryCount(6)
     */
	public function testGetActivitiesFilteredByHashTagIsOk(): void
    {
        self::$fixtureLoader->executor->getReferenceRepository();
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, ['hash_tag' => '#breakingnews'], [], ['HTTP_Authorization'=>'Bearer user1']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
	}

    /**
     * @QueryCount(6)
     *
     * @dataProvider getReferences
     */
	public function testGetActivitiesFilteredByContentIdIsOk($activityReference, $entityReference, $paramName): void
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $client = $this->client;
        /** @var Activity $activity */
        $activity = $repository->getReference($activityReference);
        $entity = $repository->getReference($entityReference);
        $params = [$paramName => $entity->getId()];
        $client->request('GET', self::API_ENDPOINT, $params, [], ['HTTP_Authorization'=>'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
        $this->assertSame($activity->getId(), $data['payload'][0]['id']);
	}

    public function getReferences()
    {
        return [
            'post' => ['activity_post', 'post_1', 'post_id'],
            'petition' => ['activity_user_petition', 'user_petition_1', 'petition_id'],
            'poll' => ['activity_question', 'group_question_3', 'poll_id'],
        ];
	}

    /**
     * @QueryCount(7)
     */
	public function testGetActivitiesByFollowingIsOk(): void
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
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
	public function testGetActivitiesByAnotherUserIsOk(): void
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
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

    /**
     * @QueryCount(7)
     */
	public function testPatchActivities(): void
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
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
        $container = $client->getContainer();
        /** @var Connection $conn */
        $conn = $container->get('doctrine')->getConnection();
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
}

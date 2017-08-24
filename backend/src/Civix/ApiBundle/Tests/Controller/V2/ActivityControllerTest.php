<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Issue\LoadLocalGroupActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityReadAuthorData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityRelationsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadEducationalContextData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSubscriberData;
use Doctrine\DBAL\Connection;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\Client;

class ActivityControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/activities';

	/**
	 * @var Client
	 */
	private $client;

	public function setUp(): void
    {
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown(): void
    {
		$this->client = NULL;
        parent::tearDown();
    }

    /**
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
            LoadPostVoteData::class,
            LoadUserPetitionSignatureData::class,
            LoadPostCommentData::class,
            LoadQuestionCommentData::class,
            LoadUserPetitionCommentData::class,
            LoadLocalGroupActivityData::class,
        ])->getReferenceRepository();
		$client = $this->client;
        foreach ($this->getSets() as $set) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $client->enableProfiler();
            $client->request('GET', self::API_ENDPOINT, $set[0], [], ['HTTP_Authorization'=>'Bearer user1']);
            $response = $client->getResponse();
            $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
            $data = $this->deserializePagination($response->getContent(), 1, count($set[1]),20);
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
                $activity = $repository->getReference($set[1][$key]);
                $this->assertActivity($item, $activity);
            }
            /** @var DoctrineDataCollector $dataCollector */
            $dataCollector = $client->getProfile()->getCollector('db');
            $this->assertSame($set[2], $dataCollector->getQueryCount());
        }
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
                10
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
                9
            ],
            'non-followed' => [
                ['non_followed' => true],
                [
                    'activity_leader_news',
                    'activity_user_petition',
                    'activity_post',
                    'activity_crowdfunding_payment_request',
                ],
                11
            ]
        ];
	}

    /**
     * @QueryCount(6)
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
     * @QueryCount(6)
     */
	public function testGetActivitiesFilteredByContentIdIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadActivityRelationsData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        $pairs = [
            'post' => [$repository->getReference('activity_post'), $repository->getReference('post_1')],
            'petition' => [$repository->getReference('activity_user_petition'), $repository->getReference('user_petition_1')],
            'poll' => [$repository->getReference('activity_question'), $repository->getReference('group_question_3')],
        ];
        $client = $this->client;
        foreach ($pairs as $key => [$activity, $entity]) {
            /** @var Activity $activity */
            $params = [$key.'_id' => $entity->getId()];
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

    /**
     * @param $item
     * @param Activity $activity
     *
     * Group by selects first row from joined table in MySQL and last one in SQLite.
     * We check getComments()->last() here but in prod (MySQL) it'll be first one.
     */
    private function assertActivity($item, Activity $activity): void
    {
        $this->assertEquals($item['id'], $activity->getId());
        $this->assertNotEmpty($item['user']);
        $this->assertArrayHasKey('group', $item);
        if ($item['entity']['type'] === 'user-petition') {
            $userPetition = $item['user_petition'];
            $this->assertTrue($userPetition['is_subscribed']);
            $this->assertCount(1, $userPetition['signatures']);
            $this->assertArrayHasKey('comments', $userPetition);
            $petition = $activity->getPetition();
            if ($petition->getComments()->count()) {
                $this->assertCount(1, $userPetition['comments']);
                $this->assertSame(
                    $petition->getComments()->last()->getId(),
                    $userPetition['comments'][0]['id']
                );
            }
        } elseif ($item['entity']['type'] === 'post') {
            $postData = $item['post'];
            $this->assertTrue($postData['is_subscribed']);
            $this->assertArrayHasKey('upvotes_count', $item);
            $this->assertArrayHasKey('downvotes_count', $item);
            $this->assertCount(1, $postData['votes']);
            $this->assertSame(Vote::OPTION_UPVOTE, $postData['votes'][0]['option']);
            $this->assertArrayHasKey('comments', $postData);
            $post = $activity->getPost();
            if ($post->getComments()->count()) {
                $this->assertCount(1, $postData['comments']);
                $this->assertSame(
                    $post->getComments()->last()->getId(),
                    $postData['comments'][0]['id']
                );
            }
        } elseif ($item['group']['group_type_label'] !== 'country' && in_array($item['entity']['type'], ['question', 'petition'], true)) {
            $this->assertNotEmpty($item['group']['avatar_file_path']);
            $pollData = $item['poll'];
            $this->assertCount(0, $pollData['answers']);
            $this->assertArrayHasKey('educational_context', $pollData);
            if ($item['entity']['type'] === 'question') {
                $this->assertTrue($pollData['is_subscribed']);
                /** @var array $educationalContexts */
                $educationalContexts = $pollData['educational_context'];
                $this->assertCount(2, $educationalContexts);
                foreach ($educationalContexts as $educationalContext) {
                    if ($educationalContext['type'] !== EducationalContext::TEXT_TYPE) {
                        $this->assertNotEmpty($educationalContext['preview']);
                    }
                }
            } else {
                $this->assertFalse($item['poll']['is_subscribed']);
            }
            $poll = $activity->getQuestion();
            if ($poll->getComments()->count()) {
                $this->assertCount(1, $pollData['comments']);
                $this->assertSame(
                    $poll->getComments()->last()->getId(),
                    $pollData['comments'][0]['id']
                );
            }
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

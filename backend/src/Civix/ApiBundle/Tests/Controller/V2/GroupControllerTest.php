<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\Group;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadInviteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupStatusData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadMembershipReportData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadPollResponseReportData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadUserReportData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GroupControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups';

	/**
	 * @var Client
	 */
	private $client;

	public function setUp(): void
    {
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown(): void
    {
		$this->client = NULL;
        parent::tearDown();
    }

	public function testGetGroupsRequestIsOk(): void
    {
        $this->loadFixtures([
            LoadUserGroupOwnerData::class,
        ]);
	    $data = [
	        [[], 4],
            [['query' => ''], 4],
            [['exclude_owned' => true], 2],
            [[
                'exclude_owned' => true,
                'sort' => 'popularity',
                'sort_dir' => 'DESC',
            ], 2],
        ];
        foreach ($data as [$query, $count]) {
            $data = $this->getGroupsRequest('user1', $query);
            $this->assertSame($count, $data['totalItems']);
            $this->assertCount($count, $data['payload']);
	    }
	}

	public function testGetGroupsSortedByCreatedAtIsOk(): void
    {
        $this->loadFixtures([
            LoadGroupData::class,
        ]);
        $data = $this->getGroupsRequest('user1', ['sort' => 'created_at', 'sort_dir' => 'DESC']);
        $this->assertSame(4, $data['totalItems']);
        $this->assertCount(4, $data['payload']);
        $current = reset($data['payload']);
        while ($next = next($data['payload'])) {
            $this->assertLessThanOrEqual(
                new \DateTime($current['created_at']),
                new \DateTime($next['created_at'])
            );
            $current = $next;
        }
    }

	public function testGetGroupsRequestSortedByPopularityIsOk(): void
    {
        $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ]);
        $data = $this->getGroupsRequest('userfollowtest1', ['sort' => 'popularity', 'sort_dir' => 'DESC']);
        $this->assertSame(4, $data['totalItems']);
        $this->assertCount(4, $data['payload']);
        $this->assertSame('group1', $data['payload'][0]['official_name']);
    }

	public function testGetGroupsRequestExcludeOwnedAndSortedByCreatedAtIsOk(): void
    {
        $this->loadFixtures([
            LoadUserGroupOwnerData::class,
        ]);
        $data = $this->getGroupsRequest('user1', [
            'exclude_owned' => true,
			'sort' => 'created_at',
			'sort_dir' => 'DESC',
		]);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
		$current = reset($data['payload']);
		while ($next = next($data['payload'])) {
			$this->assertLessThanOrEqual(
				new \DateTime($current['created_at']),
				new \DateTime($next['created_at'])
			);
			$current = $next;
		}
	}

	public function testGetGroupIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_2');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer user2']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($group->getId(), $data['id']);
        $this->assertSame(3, $data['total_members']);
        $this->assertSame('owner', $data['user_role']);
        $this->assertSame(Group::GROUP_TRANSPARENCY_PRIVATE, $data['transparency']);
        $this->assertContains('58d5e28b2a8f3.jpeg', $data['avatar_file_path']);
        $this->assertContains('b2a8f358d5e28.png', $data['banner']);
	}

    /**
     * @param $reference
     * @param $params
     * @param $errors
     * @dataProvider getInvalidValues
     */
	public function testUpdateGroupWithErrors($reference, $params, $errors): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference($reference);
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($params));
		$response = $client->getResponse();
		$this->assertResponseHasErrors($response, $errors);
	}

    public function getInvalidValues(): array
    {
        return [
            [
                'group_4',
                [
                    'official_name' => '',
                    'official_type' => '',
                    'transparency' => '',
                ],
                [
                    'official_name' => 'This value should not be blank.',
                    'official_type' => 'This value should not be blank.',
                    'transparency' => 'This value should not be blank.',
                ],
            ],
            [
                'group_1',
                [
                    'official_type' => 'yyy',
                    'transparency' => 'xxx',
                ],
                [
                    'official_type' => 'The value you selected is not a valid choice.',
                    'transparency' => 'The value you selected is not a valid choice.',
                ],
            ]
        ];
	}

	public function testUpdateGroupWithWrongPermissions(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer user2'], '');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateGroupIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$faker = Factory::create();
		$params = [
			'manager_first_name' => $faker->firstName,
			'manager_last_name' => $faker->lastName,
			'manager_email' => $faker->email,
			'manager_phone' => $faker->phoneNumber,
			'official_type' => $faker->randomElement(Group::getOfficialTypes()),
			'official_name' => $faker->company,
			'official_description' => $faker->text,
			'acronym' => $faker->company,
			'official_address' => $faker->address,
			'official_city' => $faker->city,
			'official_state' => strtoupper($faker->randomLetter.$faker->randomLetter),
            'transparency' => Group::GROUP_TRANSPARENCY_PRIVATE,
		];
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		/** @var array $data */
		$data = json_decode($response->getContent(), true);
		foreach ($data as $property => $value) {
			$this->assertSame($value, $data[$property]);
		}
	}

	public function testUpdateGroupAvatarWithWrongCredentialsThrowsException(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/avatar', [], [], ['HTTP_Authorization'=>'Bearer user2']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testAddGroupAvatarIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
		$group = $repository->getReference('group_3');
		$this->assertNull($group->getAvatarFileName());
		$params = [
            'avatar' => base64_encode(file_get_contents(__DIR__.'/../../data/image2.png'))
		];
		$client = $this->client;
        $filePath = $group->getAvatarFilePath();
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/avatar', [], [], ['HTTP_Authorization'=>'Bearer user3'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('avatar_group_fs'));
        $this->assertNotEquals($filePath, $data['avatar_file_path']);
	}

	public function testUpdateGroupAvatarIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$params = [
            'avatar' => base64_encode(file_get_contents(__DIR__.'/../../data/image2.png'))
		];
		$client = $this->client;
        $filePath = $group->getAvatarFilePath();
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/avatar', [], [], ['HTTP_Authorization'=>'Bearer user1'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('avatar_group_fs'));
        $this->assertNotEquals($filePath, $data['avatar_file_path']);
	}

    public function testDeleteGroupAvatarWithWrongCredentialsThrowsException(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user2'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/avatar', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteGroupAvatarIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $file = new UploadedFile(__DIR__.'/../../data/image.png', uniqid('', true));
        $storage->addFile($file, 'avatar_group_fs', $group->getAvatarFileName());
        $headers = ['HTTP_Authorization' => 'Bearer user1'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/avatar', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $files = $storage->getFiles('avatar_group_fs');
        $this->assertCount(1, $files);
        $newFile = reset($files);
        $this->assertNotEquals($file, $newFile);
    }

	public function testGetGroupUsersIsEmpty(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer user1'];
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

	public function testGetGroupUsersIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer user1'];
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		/** @var array $payload */
        $payload = $data['payload'];
        $this->assertCount(3, $payload);
		foreach ($payload as $item) {
			$this->assertThat(
				$item['username'],
				$this->logicalOr('user2', 'user3', 'user4')
			);
			$this->assertArrayHasKey('id', $item);
			$this->assertArrayHasKey('first_name', $item);
			$this->assertArrayHasKey('last_name', $item);
			$this->assertArrayHasKey('email', $item);
			$this->assertArrayHasKey('join_status', $item);
			$this->assertArrayHasKey('user_role', $item);
			$this->assertArrayHasKey('avatar_file_name', $item);
		}
	}

    /**
     * @param int $status
     * @dataProvider getUserStatuses
     */
	public function testGetGroupUsersByStatusIsOk($status): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupStatusData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer user1'];
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/users', ['status' => $status], [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
	}

    public function getUserStatuses(): array
    {
        return [
            ['pending'],
            ['active'],
            ['banned'],
        ];
	}

    public function testDeleteGroupUserWithWrongCredentialsThrowsException(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user4'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteGroupUserIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user3'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn(
            'SELECT * FROM users_groups WHERE user_id = ? AND group_id = ?',
            [$user->getId(), $group->getId()]
        );
        $this->assertEquals(0, $count);
    }

    public function testPatchGroupUserWithWrongCredentialsThrowsException(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user4'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testActivateGroupUserIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user3'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('active', $data['join_status']);
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $tester = new SocialActivityTester($em);
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED, $user->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
    }

    public function testBanGroupUserIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user3'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers, json_encode(['status' => 'banned']));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups WHERE user_id = ? AND group_id = ? AND status = ?', [$user->getId(), $group->getId(), UserGroup::STATUS_BANNED]);
        $this->assertEquals(1, $count);
    }

    public function testPutGroupUsersWithWrongCredentialsThrowsException(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user1'];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPutGroupUsersReturnsErrors(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $errors = [
            'users' => 'This collection should contain 1 element or more.',
            'post' => 'This value is not valid.',
            'user_petition' => 'This value is not valid.',
        ];
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user4'];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers, json_encode(['users' => [], 'post' => 0, 'user_petition' => 0]));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    public function testInviteJoinedUsersToGroupIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $user1 = $repository->getReference('user_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $service = $this->getServiceMockBuilder('civix_core.push_task')
            ->setMethods(['addToQueue'])
            ->getMock();
        $service->expects($this->never())->method('addToQueue');
        $client->getContainer()->set('civix_core.push_task', $service);
        $headers = ['HTTP_Authorization' => 'Bearer user1'];
        $params = ['users' => json_encode([
            $user1->getUsername(),
            $user2->getUsername(),
            $user3->getUsername(),
            $user4->getUsername(),
        ])];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testInviteUsersToGroupByUsernameIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user4'];
        $params = ['users' => json_encode([$user2->getUsername(), $user3->getUsername()])];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/invites', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(2, $count);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user2->getId(), $group->getId()]));
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user3->getId(), $group->getId()]));
    }

    public function testInviteUsersToGroupByEmailIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user4'];
        $params = ['users' => json_encode([$user2->getEmail(), $user3->getEmail()])];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/invites', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(2, $count);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user2->getId(), $group->getId()]));
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user3->getId(), $group->getId()]));
    }

    public function testInviteUsersToGroupByPostIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadPostVoteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        /** @var Post $post */
        $post = $repository->getReference('post_3');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user2'];
        $params = ['post' => $post->getId()];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/invites', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(2, $count);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user3->getId(), $group->getId()]));
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user4->getId(), $group->getId()]));
        $this->assertTrue($post->isSupportersWereInvited());
    }

    public function testInviteUsersToGroupByUsedPostFails(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadPostData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $post = $repository->getReference('post_6');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user3'];
        $params = ['post' => $post->getId()];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/invites', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Supporters were already invited for this post.', $data['message']);
    }

    public function testInviteUsersToGroupByUserPetitionIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadUserPetitionSignatureData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_3');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user2'];
        $params = ['user_petition' => $petition->getId()];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/invites', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(2, $count);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user3->getId(), $group->getId()]));
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user4->getId(), $group->getId()]));
        $this->assertTrue($petition->isSupportersWereInvited());
    }

    public function testInviteUsersToGroupByUsedUserPetitionFails(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $post = $repository->getReference('user_petition_6');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer user3'];
        $params = ['user_petition' => $post->getId()];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/invites', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Supporters were already invited for this petition.', $data['message']);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForInviteRequest
     */
    public function testApproveInvitationWithWrongCredentialsThrowsException($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer '.$user];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForInviteRequest
     */
    public function testApproveInvitationIsOk($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer '.$user];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups WHERE group_id = ? and user_id = ?', [$group->getId(), $user1->getId()]);
        $this->assertEquals(1, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForInviteRequest
     */
    public function testRejectInvitationWithWrongCredentialsThrowsException($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer '.$user];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForInviteRequest
     */
    public function testRejectInvitationIsOk($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer '.$user];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM notification_invites WHERE group_id = ? and user_id = ?', [$group->getId(), $user1->getId()]);
        $this->assertEquals(0, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForDeleteOwnerRequest
     */
    public function testAddGroupManagerWithWrongCredentialsThrowsException($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('userfollowtest1');
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer '.$user]);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testAddGroupManagerIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_4');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('active', $data['status']);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForDeleteOwnerRequest
     */
    public function testDeleteGroupManagerWithWrongCredentialsThrowsException($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('userfollowtest1');
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer '.$user]);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteGroupManagerIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_2');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT * FROM users_groups_managers WHERE user_id = ? AND group_id = ?', [$user->getId(), $group->getID()]);
        $this->assertEquals(0, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForDeleteOwnerRequest
     */
    public function testGetGroupMembersWithWrongCredentialsThrowsException($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/members', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$user,
        ]);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetGroupMembersIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserReportData::class,
            LoadMembershipReportData::class,
        ])->getReferenceRepository();
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $group = $repository->getReference('group_1');
        $bo = $repository->getReference('cicero_representative_bo');
        $jb = $repository->getReference('cicero_representative_jb');
        $rm = $repository->getReference('cicero_representative_rm');
        $field = $repository->getReference('test-group-field');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/members', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer user1',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('facebook', $data[0]);
        $this->assertArrayHasKey('karma', $data[0]);
        $this->assertSame($user3->getEmail(), $data[0]['email']);
        $this->assertSame($user3->getPhone(), $data[0]['phone']);
        $this->assertSame('0', $data[0]['followers']);
        $this->assertSame(20, $data[0]['karma']);
        $this->assertSame([$field->getFieldName() => 'Test Answer'], $data[0]['fields']);
        $this->assertSame([$rm->getFullName()], $data[0]['representatives']);
        $this->assertSame($user4->getEmail(), $data[1]['email']);
        $this->assertSame($user4->getPhone(), $data[1]['phone']);
        $this->assertSame('1', $data[1]['followers']);
        $this->assertSame(0, $data[1]['karma']);
        $this->assertSame([$field->getFieldName() => 'Second Answer'], $data[1]['fields']);
        $this->assertSame([$bo->getFullName(), $jb->getFullName()], $data[1]['representatives']);
    }

    public function testGetGroupMembersCsvIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserReportData::class,
            LoadMembershipReportData::class,
        ])->getReferenceRepository();
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $group = $repository->getReference('group_1');
        $bo = $repository->getReference('cicero_representative_bo');
        $jb = $repository->getReference('cicero_representative_jb');
        $rm = $repository->getReference('cicero_representative_rm');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/members', [], [], [
            'HTTP_ACCEPT' => 'text/csv',
            'HTTP_AUTHORIZATION' => 'Bearer user1',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(
            "name,address,city,state,country,zip_code,email,phone,bio,slogan,facebook,followers,karma,fields,representatives\n" .
            "\"user 3\",,,,US,,{$user3->getEmail()},{$user3->getPhone()},,,1,,20,\"test-group-field: Test Answer\",\"{$rm->getFullName()}\"\n" .
            "\"user 4\",,,,US,,{$user4->getEmail()},{$user4->getPhone()},,,1,1,,\"test-group-field: Second Answer\",\"{$bo->getFullName()}, {$jb->getFullName()}\"\n",
            $response->getContent()
        );
        $this->assertContains('text/csv', $response->headers->get('content-type'));
        $this->assertSame('attachment; filename="membership_roster.csv"', $response->headers->get('content-disposition'));
    }

    public function testGetGroupMembersCsvLinkIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/members-link', [], [], ['HTTP_AUTHORIZATION' => 'Bearer user1']);
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
        $this->assertEquals('membership_roster.csv', $file['filename']);
        $this->assertEquals('text/csv', $file['mimeType']);
        $this->assertLessThanOrEqual(new \DateTime('+2 minutes'), new \DateTime($file['expiredAt']));
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForDeleteOwnerRequest
     */
    public function testGetGroupResponsesWithWrongCredentialsThrowsException($user, $reference): void
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/responses', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$user,
        ]);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetGroupResponsesIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserReportData::class,
            LoadMembershipReportData::class,
            LoadPollResponseReportData::class,
        ])->getReferenceRepository();
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $question = $repository->getReference('group_question_1');
        /** @var Answer[] $answers */
        $answers = [
            $repository->getReference('question_answer_1'),
            $repository->getReference('question_answer_2'),
            $repository->getReference('question_answer_3'),
        ];
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/responses', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer user1',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertSame([], $data[0]['fields']);
        $this->assertSame(['test-group-field' => 'Test Answer'], $data[1]['fields']);
        $this->assertSame(['test-group-field' => 'Second Answer'], $data[2]['fields']);
        foreach ([$user2, $user3, $user4] as $k => $user) {
            /** @var User $user */
            $this->assertEquals($user->getAddress(), $data[$k]['address']);
            $this->assertSame($user->getFullName(), $data[$k]['name']);
            $this->assertSame($user->getEmail(), $data[$k]['email']);
            $this->assertSame($user->getPhone(), $data[$k]['phone']);
            $this->assertSame($user->getCity(), $data[$k]['city']);
            $this->assertSame($user->getState(), $data[$k]['state']);
            $this->assertSame($user->getCountry(), $data[$k]['country']);
            $this->assertSame($user->getZip(), $data[$k]['zip_code']);
            $this->assertSame($user->getBio(), $data[$k]['bio']);
            $this->assertEquals('1', $data[$k]['facebook']);
            if ($user === $user2) {
                $this->assertEmpty($data[$k]['representatives']);
            } else {
                $this->assertNotEmpty($data[$k]['representatives']);
            }
            if ($user === $user3) { // private
                $this->assertSame([$question->getSubject() => 'Anonymous'], $data[$k]['polls']);
                $this->assertEquals(20, $data[$k]['karma']);
            } else {
                $this->assertSame([$question->getSubject() => $answers[$k]->getOption()->getValue()], $data[$k]['polls']);
                $this->assertEquals(0, $data[$k]['karma']);
            }
            if ($user === $user4) {
                $this->assertSame('1', $data[$k]['followers']);
            } else {
                $this->assertSame('0', $data[$k]['followers']);
            }
        }
    }

    public function testGetGroupResponsesCsvIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadUserReportData::class,
            LoadMembershipReportData::class,
            LoadPollResponseReportData::class,
        ])->getReferenceRepository();
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $question = $repository->getReference('group_question_1');
        $answer1 = $repository->getReference('question_answer_1');
        $answer3 = $repository->getReference('question_answer_3');
        $group = $repository->getReference('group_1');
        $bo = $repository->getReference('cicero_representative_bo');
        $jb = $repository->getReference('cicero_representative_jb');
        $rm = $repository->getReference('cicero_representative_rm');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/responses', [], [], [
            'HTTP_ACCEPT' => 'text/csv',
            'HTTP_AUTHORIZATION' => 'Bearer user1',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(
            "name,address,city,state,country,zip_code,email,phone,bio,slogan,facebook,followers,karma,fields,representatives,polls\n" .
            "\"user 2\",,,,US,,{$user2->getEmail()},{$user2->getPhone()},,,1,,,,,\"{$question->getSubject()}: {$answer1->getOption()->getValue()}\"\n" .
            "\"user 3\",,,,US,,{$user3->getEmail()},{$user3->getPhone()},,,1,,20,\"test-group-field: Test Answer\",\"{$rm->getFullName()}\",\"{$question->getSubject()}: Anonymous\"\n" .
            "\"user 4\",,,,US,,{$user4->getEmail()},{$user4->getPhone()},,,1,1,,\"test-group-field: Second Answer\",\"{$bo->getFullName()}, {$jb->getFullName()}\",\"{$question->getSubject()}: {$answer3->getOption()->getValue()}\"\n",
            $response->getContent()
        );
        $this->assertContains('text/csv', $response->headers->get('content-type'));
        $this->assertSame('attachment; filename="file.csv"', $response->headers->get('content-disposition'));
    }

    public function testGetGroupResponsesCsvLinkIsOk(): void
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/responses-link', [], [], ['HTTP_AUTHORIZATION' => 'Bearer user1']);
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
        $this->assertNull($file['filename']);
        $this->assertEquals('text/csv', $file['mimeType']);
        $this->assertLessThanOrEqual(new \DateTime('+2 minutes'), new \DateTime($file['expiredAt']));
    }

	protected function getGroupsRequest($username, $params)
	{
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer '.$username];
		$client->request('GET', self::API_ENDPOINT, $params, [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);

		return $data;
	}

    public function getValidGroupCredentialsForInviteRequest(): array
    {
        return [
            'owner' => ['user3', 'group_3'],
            'manager' => ['user2', 'group_3'],
        ];
    }

    public function getInvalidGroupCredentialsForInviteRequest(): array
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user4', 'group_2'],
        ];
    }

    public function getInvalidGroupCredentialsForDeleteOwnerRequest(): array
    {
        return [
            'manager' => ['user2', 'group_3'],
            'member' => ['user4', 'group_3'],
            'outlier' => ['user4', 'group_2'],
        ];
    }
}

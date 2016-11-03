<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\Group;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadInviteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class GroupControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups';
	
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
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

		$this->repository = $this->loadFixtures([
			LoadUserData::class,
			LoadGroupFollowerTestData::class,
			LoadUserGroupFollowerTestData::class,
		])->getReferenceRepository();

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		$this->client = NULL;
        $this->repository = null;
        $this->em = null;
        parent::tearDown();
    }

	public function testGetGroupsIsOk()
	{
		$data = $this->getGroups('followertest', []);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

	public function testGetGroupsByQueryIsOk()
	{
		$data = $this->getGroups('followertest', ['query' => 'stfollow']);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

	public function testGetGroupsExcludeOwnedIsOk()
	{
		$data = $this->getGroups('userfollowtest1', ['exclude_owned' => true]);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
		$this->assertSame(LoadGroupFollowerTestData::GROUP_NAME, $data['payload'][0]['official_name']);
	}

	public function testGetGroupsSortedByCreatedAtIsOk()
	{
		$data = $this->getGroups('userfollowtest1', ['sort' => 'created_at', 'sort_dir' => 'DESC']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
		$current = reset($data['payload']);
		while ($next = next($data['payload'])) {
			$this->assertLessThanOrEqual(
				new \DateTime($current['created_at']),
				new \DateTime($next['created_at'])
			);
			$current = $next;
		}
	}

	public function testGetGroupsSortedByPopularityIsOk()
	{
		$data = $this->getGroups('userfollowtest1', ['sort' => 'popularity', 'sort_dir' => 'DESC']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
		$this->assertSame('testfollowprivategroups', $data['payload'][0]['official_name']);
	}

	public function testGetGroupsExcludeOwnedAndSortedByCreatedAtIsOk()
	{
		$data = $this->getGroups('testuserbookmark1', [
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

	public function testGetGroupsExcludeOwnedAndSortedByPopularityIsOk()
	{
		$data = $this->getGroups('testuserbookmark1', [
			'exclude_owned' => true,
			'sort' => 'popularity',
			'sort_dir' => 'DESC',
		]);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

	public function testGetGroupNotAuthorized()
	{
		$group = $this->repository->getReference('group');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId());
		$response = $client->getResponse();
		$this->assertEquals(401, $response->getStatusCode(), $response->getContent());
	}

	public function testGetGroupIsOk()
	{
		$group = $this->repository->getReference('testfollowprivategroups');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($group->getId(), $data['id']);
        $this->assertSame(4, $data['total_members']);
        $this->assertSame(Group::GROUP_TRANSPARENCY_PRIVATE, $data['transparency']);
	}

    /**
     * @param $params
     * @param $errors
     * @dataProvider getInvalidValues
     */
	public function testUpdateGroupWithErrors($params, $errors)
	{
		$group = $this->repository->getReference('testfollowsecretgroups');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertResponseHasErrors($response, $errors);
	}

    public function getInvalidValues()
    {
        return [
            [
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

	public function testUpdateGroupWithWrongPermissions()
	{
		$group = $this->repository->getReference('group');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'], '');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateGroupIsOk()
	{
		$group = $this->repository->getReference('testfollowsecretgroups');
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
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($data as $property => $value) {
			$this->assertSame($value, $data[$property]);
		}
	}

	public function testGetGroupUsersIsEmpty()
	{
		$group = $this->repository->getReference('group');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="userfollowtest1"'];
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

	public function testGetGroupUsersIsOk()
	{
		$group = $this->repository->getReference('testfollowsecretgroups');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="userfollowtest1"'];
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
		foreach ($data['payload'] as $item) {
			$this->assertThat(
				$item['username'],
				$this->logicalOr('userfollowtest1', 'userfollowtest2', 'userfollowtest3')
			);
			$this->assertArrayHasKey('id', $item);
			$this->assertArrayHasKey('first_name', $item);
			$this->assertArrayHasKey('last_name', $item);
			$this->assertArrayHasKey('email', $item);
			$this->assertArrayHasKey('join_status', $item);
			$this->assertArrayHasKey('user_role', $item);
		}
	}

    public function testDeleteGroupUserWithWrongCredentialsThrowsException()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_2');
        $user = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteGroupUserIsOk()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_2');
        $user = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn(
            'SELECT * FROM users_groups WHERE user_id = ? AND group_id = ?',
            [$user->getId(), $group->getId()]
        );
        $this->assertEquals(0, $count);
    }

    public function testPatchGroupUserWithWrongCredentialsThrowsException()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_2');
        $user = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPatchGroupUserIsOk()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_2');
        $user = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $tester = new SocialActivityTester($client->getContainer()->get('doctrine.orm.entity_manager'));
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED, $user->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
    }

    public function testPutGroupUsersWithWrongCredentialsThrowsException()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user1"'];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPutGroupUsersReturnsErrors()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers, json_encode(['users' => []]));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals([
            'This value should not be blank.',
            'This collection should contain 1 element or more.',
        ], $data['errors']['children']['users']['errors']);
    }

    public function testInviteJoinedUsersToGroupIsOk()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_1');
        $user1 = $this->repository->getReference('user_1');
        $user2 = $this->repository->getReference('user_2');
        $user3 = $this->repository->getReference('user_3');
        $user4 = $this->repository->getReference('user_4');
        $client = $this->client;
        $service = $this->getServiceMockBuilder('civix_core.push_task')
            ->setMethods(['addToQueue'])
            ->getMock();
        $service->expects($this->never())->method('addToQueue');
        $client->getContainer()->set('civix_core.push_task', $service);
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user1"'];
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
        $conn = $client->getContainer()->get('database_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testInviteUsersToGroupIsOk()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_1');
        $user2 = $this->repository->getReference('user_2');
        $user3 = $this->repository->getReference('user_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
        $params = ['users' => json_encode([$user2->getUsername(), $user3->getUsername()])];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(2, $count);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user2->getId(), $group->getId()]));
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendGroupInvitePush', [$user3->getId(), $group->getId()]));
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForInviteRequest
     */
    public function testApproveInvitationWithWrongCredentialsThrowsException($user, $reference)
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference($reference);
        $user1 = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user.'"'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForInviteRequest
     */
    public function testApproveInvitationIsOk($user, $reference)
    {
        $this->repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference($reference);
        $user1 = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user.'"'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups WHERE group_id = ? and user_id = ?', [$group->getId(), $user1->getId()]);
        $this->assertEquals(1, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForInviteRequest
     */
    public function testRejectInvitationWithWrongCredentialsThrowsException($user, $reference)
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference($reference);
        $user1 = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user.'"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForInviteRequest
     */
    public function testRejectInvitationIsOk($user, $reference)
    {
        $this->repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference($reference);
        $user1 = $this->repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user.'"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/invites/'.$user1->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM notification_invites WHERE group_id = ? and user_id = ?', [$group->getId(), $user1->getId()]);
        $this->assertEquals(0, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForDeleteOwnerRequest
     */
    public function testDeleteOwnerWithWrongCredentialsThrowsException($user, $reference)
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference($reference);
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user.'"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/owner', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteOwnerSetsManager()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_3');
        $user = $this->repository->getReference('user_2');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/owner', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $userId = $conn->fetchColumn('SELECT user_id FROM groups WHERE id = ?', [$group->getId()]);
        $this->assertEquals($user->getId(), $userId);
    }

    public function testDeleteOwnerSetsMember()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_3');
        $user = $this->repository->getReference('user_4');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/owner', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $userId = $conn->fetchColumn('SELECT user_id FROM groups WHERE id = ?', [$group->getId()]);
        $this->assertEquals($user->getId(), $userId);
    }

    public function testDeleteOwnerSetsNull()
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        $group = $this->repository->getReference('group_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/owner', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $userId = $conn->fetchColumn('SELECT user_id FROM groups WHERE id = ?', [$group->getId()]);
        $this->assertEquals(null, $userId);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForDeleteOwnerRequest
     */
    public function testAddGroupManagerWithWrongCredentialsThrowsException($user, $reference)
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $this->repository->getReference('userfollowtest1');
        $group = $this->repository->getReference($reference);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testAddGroupManagerIsOk()
    {
        $user = $this->repository->getReference('userfollowtest2');
        $group = $this->repository->getReference('testfollowsecretgroups');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']);
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
    public function testDeleteGroupManagerWithWrongCredentialsThrowsException($user, $reference)
    {
        $this->repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $this->repository->getReference('userfollowtest1');
        $group = $this->repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteGroupManagerIsOk()
    {
        $this->repository = $this->loadFixtures([
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user = $this->repository->getReference('user_2');
        $group = $this->repository->getReference('group_1');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT * FROM users_groups_managers WHERE user_id = ? AND group_id = ?', [$user->getId(), $group->getID()]);
        $this->assertEquals(0, $count);
    }

	protected function getGroups($username, $params)
	{
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$username.'"'];
		$client->request('GET', self::API_ENDPOINT, $params, [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		return $data;
	}

    public function getValidGroupCredentialsForInviteRequest()
    {
        return [
            'owner' => ['user3', 'group_3'],
            'manager' => ['user2', 'group_3'],
        ];
    }

    public function getInvalidGroupCredentialsForInviteRequest()
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user4', 'group_2'],
        ];
    }

    public function getInvalidGroupCredentialsForDeleteOwnerRequest()
    {
        return [
            'manager' => ['user2', 'group_3'],
            'member' => ['user4', 'group_3'],
            'outlier' => ['user4', 'group_2'],
        ];
    }
}

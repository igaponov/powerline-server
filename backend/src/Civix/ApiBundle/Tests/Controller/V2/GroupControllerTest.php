<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\Group;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadFieldValueData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadInviteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeReportData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class GroupControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups';
	
	/**
	 * @var Client
	 */
	private $client = null;

	public function setUp()
	{
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		$this->client = NULL;
        parent::tearDown();
    }

	public function testGetGroupsIsOk()
	{
        $this->loadFixtures([
            LoadGroupData::class,
        ]);
		$data = $this->getGroups('user1', []);
		$this->assertSame(4, $data['totalItems']);
		$this->assertCount(4, $data['payload']);
	}

	public function testGetGroupsByQueryIsOk()
	{
        $this->loadFixtures([
            LoadGroupData::class,
        ]);
		$data = $this->getGroups('user1', ['query' => '']);
		$this->assertSame(4, $data['totalItems']);
		$this->assertCount(4, $data['payload']);
	}

	public function testGetGroupsExcludeOwnedIsOk()
	{
        $this->loadFixtures([
            LoadUserGroupOwnerData::class,
        ]);
		$data = $this->getGroups('user1', ['exclude_owned' => true]);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

	public function testGetGroupsSortedByCreatedAtIsOk()
	{
        $this->loadFixtures([
            LoadGroupData::class,
        ]);
		$data = $this->getGroups('user1', ['sort' => 'created_at', 'sort_dir' => 'DESC']);
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

	public function testGetGroupsSortedByPopularityIsOk()
	{
        $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ]);
		$data = $this->getGroups('userfollowtest1', ['sort' => 'popularity', 'sort_dir' => 'DESC']);
		$this->assertSame(4, $data['totalItems']);
		$this->assertCount(4, $data['payload']);
		$this->assertSame('group1', $data['payload'][0]['official_name']);
	}

	public function testGetGroupsExcludeOwnedAndSortedByCreatedAtIsOk()
	{
        $this->loadFixtures([
            LoadUserGroupOwnerData::class,
        ]);
		$data = $this->getGroups('user1', [
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
        $this->loadFixtures([
            LoadUserGroupOwnerData::class,
        ]);
		$data = $this->getGroups('user1', [
			'exclude_owned' => true,
			'sort' => 'popularity',
			'sort_dir' => 'DESC',
		]);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

	public function testGetGroupNotAuthorized()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId());
		$response = $client->getResponse();
		$this->assertEquals(401, $response->getStatusCode(), $response->getContent());
	}

	public function testGetGroupIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_2');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($group->getId(), $data['id']);
        $this->assertSame(3, $data['total_members']);
        $this->assertSame('owner', $data['user_role']);
        $this->assertSame(Group::GROUP_TRANSPARENCY_PRIVATE, $data['transparency']);
	}

    /**
     * @param $params
     * @param $errors
     * @dataProvider getInvalidValues
     */
	public function testUpdateGroupWithErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
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
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"'], '');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateGroupIsOk()
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
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($data as $property => $value) {
			$this->assertSame($value, $data[$property]);
		}
	}

	public function testGetGroupUsersIsEmpty()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="user1"'];
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
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
		$group = $repository->getReference('group_1');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="user1"'];
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

    public function testDeleteGroupUserWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteGroupUserIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
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

    public function testPatchGroupUserWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPatchGroupUserIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $user = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('PATCH', self::API_ENDPOINT.'/'.$group->getId().'/users/'.$user->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $tester = new SocialActivityTester($client->getContainer()->get('doctrine')->getManager());
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_JOIN_TO_GROUP_APPROVED, $user->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
    }

    public function testPutGroupUsersWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user1"'];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPutGroupUsersReturnsErrors()
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
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers, json_encode(['users' => [], 'post' => 0, 'user_petition' => 0]));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    public function testInviteJoinedUsersToGroupIsOk()
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
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM invites WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testInviteUsersToGroupByUsernameIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user4"'];
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

    public function testInviteUsersToGroupByPostIsOk()
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
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user2"'];
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

    public function testInviteUsersToGroupByUsedPostFails()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadPostData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $post = $repository->getReference('post_6');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $params = ['post' => $post->getId()];
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/invites', [], [], $headers, json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Supporters were already invited for this post.', $data['message']);
    }

    public function testInviteUsersToGroupByUserPetitionIsOk()
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
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user2"'];
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

    public function testInviteUsersToGroupByUsedUserPetitionFails()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $post = $repository->getReference('user_petition_6');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
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
    public function testApproveInvitationWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
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
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user.'"'];
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
    public function testRejectInvitationWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
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
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadInviteData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $user1 = $repository->getReference('user_1');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$user.'"'];
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
    public function testAddGroupManagerWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('userfollowtest1');
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testAddGroupManagerIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_4');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
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
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('userfollowtest1');
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user1->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteGroupManagerIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_2');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId().'/managers/'.$user->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
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
    public function testGetGroupMembersWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/members', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer type="user" token="'.$user.'"',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetGroupMembersIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadGroupManagerData::class,
            LoadFieldValueData::class,
            LoadUserRepresentativeReportData::class,
        ])->getReferenceRepository();
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $group = $repository->getReference('group_1');
        $bo = $repository->getReference('cicero_representative_bo');
        $jb = $repository->getReference('cicero_representative_jb');
        $kg = $repository->getReference('cicero_representative_kg');
        $eh = $repository->getReference('cicero_representative_eh');
        $rm = $repository->getReference('cicero_representative_rm');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/members', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer type="user" token="user1"',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('first_name', $data[0]);
        $this->assertArrayHasKey('last_name', $data[0]);
        $this->assertArrayHasKey('facebook', $data[0]);
        $this->assertArrayHasKey('karma', $data[0]);
        $this->assertSame($user2->getEmail(), $data[0]['email']);
        $this->assertSame($user2->getPhone(), $data[0]['phone']);
        $this->assertSame("1", $data[0]['followers']);
        $this->assertSame("test-field-value-2", $data[0]['test-group-field']);
        $this->assertNull($data[0]['president']);
        $this->assertNull($data[0]['vice_president']);
        $this->assertNull($data[0]['senator1']);
        $this->assertNull($data[0]['senator2']);
        $this->assertNull($data[0]['congressman']);
        $this->assertSame($user3->getEmail(), $data[1]['email']);
        $this->assertSame($user3->getPhone(), $data[1]['phone']);
        $this->assertSame("1", $data[1]['followers']);
        $this->assertSame("test-field-value-3", $data[1]['test-group-field']);
        $this->assertSame($bo->getFullName(), $data[1]['president']);
        $this->assertSame($jb->getFullName(), $data[1]['vice_president']);
        $this->assertSame($rm->getFullName(), $data[1]['senator1']);
        $this->assertSame($kg->getFullName(), $data[1]['senator2']);
        $this->assertSame($eh->getFullName(), $data[1]['congressman']);
    }

    public function testGetGroupMembersCsvIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadGroupManagerData::class,
            LoadFieldValueData::class,
            LoadUserRepresentativeReportData::class,
        ])->getReferenceRepository();
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $group = $repository->getReference('group_1');
        $bo = $repository->getReference('cicero_representative_bo');
        $jb = $repository->getReference('cicero_representative_jb');
        $kg = $repository->getReference('cicero_representative_kg');
        $eh = $repository->getReference('cicero_representative_eh');
        $rm = $repository->getReference('cicero_representative_rm');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/members', [], [], [
            'HTTP_ACCEPT' => 'text/csv',
            'HTTP_AUTHORIZATION' => 'Bearer type="user" token="user1"',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(
            "first_name,last_name,address1,address2,city,state,country,zip,email,phone,bio,slogan,facebook,followers,karma," .
            "test-group-field,\"\"\"field1`\",\"\"\"field2`\",\"\"\"field3`\",\"\"\"field4`\",president,vice_president,senator1,senator2,congressman\n" .
            "user,2,,,,,US,,{$user2->getEmail()},{$user2->getPhone()},,,1,1,0,test-field-value-2,,,,,,,,,\n" .
            "user,3,,,,,US,,{$user3->getEmail()},{$user3->getPhone()},,,1,1,0,test-field-value-3,,,,,\"{$bo->getFullName()}\",\"{$jb->getFullName()}\",\"{$rm->getFullName()}\",\"{$kg->getFullName()}\",\"{$eh->getFullName()}\"\n",
            $response->getContent()
        );
        $this->assertContains('text/csv', $response->headers->get('content-type'));
        $this->assertSame('attachment; filename="membership_roster.csv"', $response->headers->get('content-disposition'));
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForDeleteOwnerRequest
     */
    public function testGetGroupResponsesWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/responses', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer type="user" token="'.$user.'"',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetGroupResponsesIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadGroupManagerData::class,
            LoadFieldValueData::class,
            LoadQuestionAnswerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('user_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $poll1 = $repository->getReference('group_question_1');
        $poll5 = $repository->getReference('group_question_5');
        $answer1 = $repository->getReference('question_answer_1');
        $answer3 = $repository->getReference('question_answer_3');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/responses', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer type="user" token="user1"',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);

        $this->assertCount(9, $data);
        $this->assertArrayHasKey('first_name', $data[0]);
        $this->assertArrayHasKey('last_name', $data[0]);
        $this->assertArrayHasKey('facebook', $data[0]);
        $this->assertArrayHasKey('karma', $data[0]);
        $this->assertSame($user1->getEmail(), $data[0]['email']);
        $this->assertSame($user1->getPhone(), $data[0]['phone']);
        $this->assertSame("0", $data[0]['followers']);
        $this->assertNull($data[0]['test-group-field']);
        $this->assertNull($data[0][$poll1->getSubject()]);
        $this->assertNull($data[0][$poll5->getSubject()]);

        $this->assertSame($user2->getEmail(), $data[1]['email']);
        $this->assertSame($user2->getPhone(), $data[1]['phone']);
        $this->assertSame("1", $data[1]['followers']);
        $this->assertSame("test-field-value-2", $data[1]['test-group-field']);
        $this->assertSame($answer1->getOption()->getValue(), $data[1][$poll1->getSubject()]);
        $this->assertNull($data[1][$poll5->getSubject()]);

        $this->assertSame($user3->getEmail(), $data[2]['email']);
        $this->assertSame($user3->getPhone(), $data[2]['phone']);
        $this->assertSame("1", $data[2]['followers']);
        $this->assertSame("test-field-value-3", $data[2]['test-group-field']);
        $this->assertSame("Anonymous", $data[2][$poll1->getSubject()]);
        $this->assertNull($data[2][$poll5->getSubject()]);

        $this->assertSame($user4->getEmail(), $data[3]['email']);
        $this->assertSame($user4->getPhone(), $data[3]['phone']);
        $this->assertSame("1", $data[3]['followers']);
        $this->assertNull($data[3]['test-group-field']);
        $this->assertSame($answer3->getOption()->getValue(), $data[3][$poll1->getSubject()]);
        $this->assertNull($data[3][$poll5->getSubject()]);
    }

    public function testGetGroupResponsesCsvIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadGroupManagerData::class,
            LoadFieldValueData::class,
            LoadQuestionAnswerData::class,
        ])->getReferenceRepository();
        $user1 = $repository->getReference('user_1');
        $user2 = $repository->getReference('user_2');
        $user3 = $repository->getReference('user_3');
        $user4 = $repository->getReference('user_4');
        $followertest = $repository->getReference('followertest');
        $userfollowtest1 = $repository->getReference('userfollowtest1');
        $userfollowtest2 = $repository->getReference('userfollowtest2');
        $userfollowtest3 = $repository->getReference('userfollowtest3');
        $testuserbookmark1 = $repository->getReference('testuserbookmark1');
        $poll1 = $repository->getReference('group_question_1');
        $poll5 = $repository->getReference('group_question_5');
        $answer1 = $repository->getReference('question_answer_1');
        $answer3 = $repository->getReference('question_answer_3');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/responses', [], [], [
            'HTTP_ACCEPT' => 'text/csv',
            'HTTP_AUTHORIZATION' => 'Bearer type="user" token="user1"',
        ]);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(
            "first_name,last_name,address1,address2,city,state,country,zip,email,phone,bio,slogan,facebook,followers,karma," .
            "test-group-field,\"\"\"field1`\",\"\"\"field2`\",\"\"\"field3`\",\"\"\"field4`\"," .
            "\"{$poll1->getSubject()}\",\"{$poll5->getSubject()}\"\n" .
            "User,One,,,,,US,,{$user1->getEmail()},{$user1->getPhone()},\"{$user1->getBio()}\",\"{$user1->getSlogan()}\",1,0,0,,,,,,,\n" .
            "user,2,,,,,US,,{$user2->getEmail()},{$user2->getPhone()},,,1,1,0,test-field-value-2,,,,,\"{$answer1->getOption()->getValue()}\",\n" .
            "user,3,,,,,US,,{$user3->getEmail()},{$user3->getPhone()},,,1,1,0,test-field-value-3,,,,,Anonymous,\n" .
            "user,4,,,,,US,,{$user4->getEmail()},{$user4->getPhone()},,,1,1,0,,,,,,\"{$answer3->getOption()->getValue()}\",\n" .
            "followertest,,,,,,US,,{$followertest->getEmail()},{$followertest->getPhone()},,,1,0,0,,,,,,,\n" .
            "userfollowtest,1,,,,,US,,{$userfollowtest1->getEmail()},{$userfollowtest1->getPhone()},,,1,0,0,,,,,,,\n" .
            "userfollowtest,2,,,,,US,,{$userfollowtest2->getEmail()},{$userfollowtest2->getPhone()},,,1,0,0,,,,,,,\n" .
            "userfollowtest,3,,,,,US,,{$userfollowtest3->getEmail()},{$userfollowtest3->getPhone()},,,1,0,0,,,,,,,\n" .
            "testuserbookmark,1,,,,,US,,{$testuserbookmark1->getEmail()},{$testuserbookmark1->getPhone()},,,1,0,0,,,,,,,\n",
            $response->getContent()
        );
        $this->assertContains('text/csv', $response->headers->get('content-type'));
        $this->assertSame('attachment; filename="file.csv"', $response->headers->get('content-disposition'));
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

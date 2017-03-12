<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Model\Subscription\PackageLimitState;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Service\Subscription\PackageHandler;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFieldsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadMembershipReportData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class UserGroupControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user/groups';

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
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

    public function testGetGroups()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        $group1 = $repository->getReference('group_1');
        $group2 = $repository->getReference('group_2');
        $group3 = $repository->getReference('group_3');
        $group4 = $repository->getReference('group_4');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(4, $data['totalItems']);
        $this->assertCount(4, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertArrayHasKey('official_name', $item);
            $this->assertArrayHasKey('join_status', $item);
            switch ($item['id']) {
                case $group1->getId():
                case $group2->getId():
                    $this->assertSame('manager', $item['user_role']);
                    break;
                case $group3->getId():
                    $this->assertSame('owner', $item['user_role']);
                    break;
                case $group4->getId():
                    $this->assertSame('member', $item['user_role']);
                    break;
            }
        }
    }

    public function testGetGroupsIsEmpty()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(50, $data['items']);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }

    public function testCreateGroupWithErrors()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $errors = [
            'official_name' => 'This value should not be blank.',
            'official_type' => 'This value should not be blank.',
            'transparency' => 'This value should not be blank.',
        ];
        $client = $this->client;
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], json_encode([
            'official_name' => '',
            'official_type' => '',
            'transparency' => '',
        ]));
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, $errors);
    }

    public function testCreateGroupIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('followertest');
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
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->setMethods(['createAccount'])
            ->getMock();
        $service->expects($this->once())
            ->method('createAccount')
            ->with($this->isInstanceOf(Group::class));
        $client->getContainer()->set('civix_core.stripe', $service);
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        foreach ($params as $property => $value) {
            $this->assertSame($value, $data[$property]);
        }
        $this->assertSame(Group::GROUP_TRANSPARENCY_PUBLIC, $data['transparency']);
        $this->assertSame([
            Group::PERMISSIONS_NAME,
            Group::PERMISSIONS_COUNTRY,
            Group::PERMISSIONS_RESPONSES,
        ], $data['required_permissions']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups WHERE group_id = ? and user_id = ?', [$data['id'], $user->getId()]);
        $this->assertEquals(1, $count);
    }

    /**
     * @QueryCount(14)
     * @todo see testJoinGroupWithFieldsIsOk
     * @param $reference
     * @param $status
     * @dataProvider getJoinedGroupStatuses
     */
    public function testJoinGroupIsOk($reference, $status)
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($status == UserGroup::STATUS_ACTIVE ? 'active' : 'pending', $data['join_status']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $currentStatus = $conn->fetchColumn('SELECT status FROM users_groups WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals($status, $currentStatus);
        $result = $this->em->getRepository(MembershipReport::class)
            ->getMembershipReport($group);
        $this->assertEquals($group->getId(), $result[0]['group']);
        $this->assertEquals([], $result[0]['fields']);
    }

    public function getJoinedGroupStatuses()
    {
        return [
            'public' => ['group_1', UserGroup::STATUS_ACTIVE],
            'private' => ['group_2', UserGroup::STATUS_PENDING],
        ];
    }

    /**
     * @QueryCount(17)
     * Queries:
     * 1. Get user
     * 2. Get group
     * 3. Get group fields for group
     * 4. Get notification_invites (get rid?)
     * 5. Check if the user is already joined
     * 6. Delete invites
     * 7-9. Insert user group in transaction
     * 10-12. Insert social activity "join-to-group-approved" in transaction
     * 13-15. Insert group field values in transaction
     * 16. Replace membership report
     * 17. Get a user's role for a group (serializer, JoinStatusHandler)
     * @todo get rid from one invite? Move all queries to one transaction.
     */
    public function testJoinGroupWithFieldsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_4');
        /** @var Group $group */
        $group = $repository->getReference('group_3');
        /** @var Group\GroupField $field */
        $field = $repository->getReference('another-group-field');
        $fieldValue = 'Answer A';
        $params = [
            'passcode' => 'secret_passcode',
            'answered_fields' => [
                [
                    'id' => $field->getId(),
                    'value' => $fieldValue,
                ],
            ],
        ];
        $client = $this->client;
        $service = $this->getMockBuilder(PackageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('getPackageStateForGroupSize')
            ->with($this->isInstanceOf(Group::class))
            ->willReturn(new PackageLimitState());
        $client->getContainer()->set('civix_core.package_handler', $service);
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('active', $data['join_status']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $currentStatus = $conn->fetchColumn('SELECT status FROM users_groups WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(UserGroup::STATUS_ACTIVE, $currentStatus);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM groups_fields_values WHERE field_value = ? AND user_id = ?', [$fieldValue, $user->getId()]);
        $this->assertEquals(1, $count);
        $result = $this->em->getRepository(MembershipReport::class)
            ->getMembershipReport($group);
        $this->assertEquals($group->getId(), $result[0]['group']);
        $this->assertEquals([$field->getId() => $fieldValue], $result[0]['fields']);
    }

    public function testJoinGroupWithErrors()
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $service = $this->getMockBuilder(PackageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $state = new PackageLimitState();
        $state->setLimitValue(1)
            ->setCurrentValue(2);
        $service->expects($this->once())
            ->method('getPackageStateForGroupSize')
            ->with($this->isInstanceOf(Group::class))
            ->willReturn($state);
        $client->getContainer()->set('civix_core.package_handler', $service);
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, [
            'The group is full.',
            'Please fill group\'s required fields.',
            'passcode' => 'Incorrect passcode.',
        ]);
    }

    /**
     * @QueryCount(4)
     */
    public function testUnjoinGroupIsOk()
    {
        $repository = $this->loadFixtures([
            LoadMembershipReportData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $user = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups WHERE group_id = ? AND user_id = ?', [$group->getId(), $user->getId()]);
        $this->assertEquals(0, $count);
        $result = $this->em->getRepository(MembershipReport::class)
            ->getMembershipReport($group);
        $this->assertEmpty($result);
    }

    /**
     * @QueryCount(9)
     * 7. Get group's fields to count
     * @todo get rid of Group::updateFillFieldsRequired()
     */
    public function testOwnerUnjoinGroupSetsManagerAsOwner()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $user = $repository->getReference('user_2');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $userId = $conn->fetchColumn('SELECT user_id FROM groups WHERE id = ?', [$group->getId()]);
        $this->assertEquals($user->getId(), $userId);
    }

    public function testOwnerUnjoinGroupSetsMemberAsOwner()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $user = $repository->getReference('user_4');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $userId = $conn->fetchColumn('SELECT user_id FROM groups WHERE id = ?', [$group->getId()]);
        $this->assertEquals($user->getId(), $userId);
    }

    public function testOwnerUnjoinGroupSetsNullAsOwner()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $headers = ['HTTP_Authorization' => 'Bearer type="user" token="user3"'];
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId(), [], [], $headers);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $userId = $conn->fetchColumn('SELECT user_id FROM groups WHERE id = ?', [$group->getId()]);
        $this->assertEquals(null, $userId);
    }
}
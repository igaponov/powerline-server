<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFieldsData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
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
            $this->assertArrayHasKey('username', $item);
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
            'username' => ['This value should not be blank.'],
            'official_name' => ['This value should not be blank.'],
            'official_type' => ['This value should not be blank.'],
        ];
        $client = $this->client;
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $count = 0;
        foreach ($data['errors']['children'] as $property => $arr) {
            if (!empty($arr['errors'])) {
                $count++;
                $this->assertSame($errors[$property], $arr['errors']);
            }
        }
        $this->assertCount($count, $errors);
    }

    public function testCreateGroupIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('followertest');
        $faker = Factory::create();
        $params = [
            'username' => $faker->userName,
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
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        foreach ($params as $property => $value) {
            $this->assertSame($value, $data[$property]);
        }
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('database_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups WHERE group_id = ? and user_id = ?', [$data['id'], $user->getId()]);
        $this->assertEquals(1, $count);
    }

    /**
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
        $group = $repository->getReference($reference);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $currentStatus = $conn->fetchColumn('SELECT status FROM users_groups WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals($status, $currentStatus);
    }

    public function getJoinedGroupStatuses()
    {
        return [
            'public' => ['group_1', UserGroup::STATUS_ACTIVE],
            'private' => ['group_2', UserGroup::STATUS_PENDING],
        ];
    }

    public function testJoinGroupWithFieldsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupFieldsData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        /** @var Group\GroupField $field */
        $field = $repository->getReference('another-group-field');
        $fieldValue = 'Answer A';
        $params = [
            'passcode' => 'secret_passcode',
            'fields' => [
                [
                    'field' => [
                        'id' => $field->getId(),
                    ],
                    'field_value' => $fieldValue
                ],
            ],
        ];
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $currentStatus = $conn->fetchColumn('SELECT status FROM users_groups WHERE group_id = ?', [$group->getId()]);
        $this->assertEquals(UserGroup::STATUS_ACTIVE, $currentStatus);
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM groups_fields_values WHERE field_value = ?', [$fieldValue]);
        $this->assertEquals(1, $count);
    }

    public function testJoinGroupWithPasscodeThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Incorrect passcode', $data['message']);
    }

    public function testUnjoinGroupIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $user = $repository->getReference('user_4');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user4"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM users_groups WHERE group_id = ? AND user_id = ?', [$group->getId(), $user->getId()]);
        $this->assertEquals(0, $count);
    }
}
<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Service\UserPetitionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPetitionControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{group}/user-petitions';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown()
    {
        $this->client = null;
        parent::tearDown();
    }

    public function testCreateUserPetitionWithErrors()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $manager = $this->getPetitionManagerMock(['checkPetitionLimitPerMonth']);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(false));
        $this->client->getContainer()->set('civix_core.user_petition_manager', $manager);
        $expectedErrors = [
            'Your limit of petitions per month is reached.',
            'body' => 'This value should not be blank.',
        ];
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $errors = $data['errors'];
        foreach ($expectedErrors as $child => $error) {
            if (is_int($child)) {
                $this->assertContains($error, $errors['errors']);
            } elseif ($error) {
                $this->assertContains($error, $errors['children'][$child]['errors']);
            } else {
                $this->assertEmpty($errors['children'][$child]);
            }
        }
    }

    public function testCreateUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkPetitionLimitPerMonth',
        ]);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.user_petition_manager', $manager);
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'title' => $faker->sentence,
            'body' => $faker->text."\n".implode(' ', $hashTags),
            'is_outsiders_sign' => true,
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['body'], $data['body']);
        $this->assertSame($params['is_outsiders_sign'], $data['is_outsiders_sign']);
        $this->assertTrue($data['boosted']);
        $this->assertFalse($data['organization_needed']);
        // check addHashTags event listener
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags_petitions');
        $this->assertCount($count, $hashTags);
        $this->assertCount($count, $data['cached_hash_tags']);
        // check root comment
        $body = $conn->fetchColumn('SELECT comment_body FROM user_petition_comments WHERE petition_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $body);
        // check social activity
        $type = $conn->fetchColumn('SELECT type FROM social_activities WHERE group_id = ?', [$group->getId()]);
        $this->assertSame(SocialActivity::TYPE_GROUP_USER_PETITION_CREATED, $type);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $description);
        // check author subscription
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM petition_subscribers WHERE userpetition_id = ?', [$data['id']]);
        $this->assertEquals(1, $count);
    }

    public function testGetActivitiesOfDeletedUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkPetitionLimitPerMonth',
        ]);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.user_petition_manager', $manager);
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'title' => $faker->sentence,
            'body' => $faker->text."\n".implode(' ', $hashTags),
            'is_outsiders_sign' => true,
        ];
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM activities');
        $this->assertEquals(0, $count);
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $description);
        $client->request('DELETE',
            '/api/v2/user-petitions/'.$data['id'], [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM user_petitions WHERE id = ?', [$data['id']]);
        $this->assertEquals(0, $count);
        $client->request('GET',
            '/api/v2/activities', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(0, $data['payload']);
    }

    public function testCreateOpenLetter()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkPetitionLimitPerMonth',
        ]);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.user_petition_manager', $manager);
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag2',
            '#powerlineHashTag2',
        ];
        $params = [
            'title' => $faker->sentence,
            'body' => $faker->text."\n".implode(' ', $hashTags),
            'is_outsiders_sign' => $faker->boolean(),
            'organization_needed' => true,
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['title'], $data['title']);
        $this->assertSame($params['body'], $data['body']);
        $this->assertSame($params['is_outsiders_sign'], $data['is_outsiders_sign']);
        $this->assertSame($params['organization_needed'], $data['organization_needed']);
        $this->assertTrue($data['boosted']);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UserPetitionManager
     */
    private function getPetitionManagerMock($methods = [])
    {
        $container = $this->client->getContainer();
        return $this->getMockBuilder(UserPetitionManager::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                $container->get('doctrine.orm.entity_manager'),
                $container->get('event_dispatcher')
            ])
            ->getMock();
    }
}
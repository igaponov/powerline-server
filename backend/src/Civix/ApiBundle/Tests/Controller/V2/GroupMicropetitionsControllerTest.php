<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupFollowerTestData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class GroupMicropetitionsControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{id}/micro-petitions';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function testCreateMicropetitionWithErrors()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
        ])->getReferenceRepository();
        $manager = $this->getPetitionManagerMock(['checkPetitionLimitPerMonth']);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(false));
        $this->client->getContainer()->set('civix_core.poll.micropetition_manager', $manager);
        $expectedErrors = [
            'Your limit of petitions per month is reached.',
            'petition_body' => 'This value should not be blank.',
            'type' => 'The value you selected is not a valid choice.',
        ];
        $group = $repository->getReference('testfollowprivategroups');
        $client = $this->client;
        $uri = str_replace('{id}', $group->getId(), self::API_ENDPOINT);
        $params = [
            'type' => 'invalid type',
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'],
            json_encode($params)
        );
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

    public function testCreateMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $group = $repository->getReference('testfollowprivategroups');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkPetitionLimitPerMonth',
        ]);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.poll.micropetition_manager', $manager);
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{id}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'title' => $faker->sentence,
            'petition_body' => $faker->text."\n".implode(' ', $hashTags),
            'link' => $faker->url,
            'is_outsiders_sign' => $faker->boolean(),
            'type' => Petition::TYPE_OPEN_LETTER,
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data['title']);
        $this->assertSame($params['petition_body'], $data['petition_body']);
        $this->assertSame($params['link'], $data['link']);
        $this->assertSame($params['is_outsiders_sign'], $data['is_outsiders_sign']);
        $this->assertSame($params['type'], $data['type']);
        $this->assertSame($group->getId(), $data['group']['id']);
        $this->assertSame('userfollowtest1', $data['user']['username']);
        $this->assertSame(Petition::STATUS_USER, $data['publish_status']);
        // check setExpire event listener
        $this->assertSame(100, $data['user_expire_interval']);
        $this->assertThat(
            strtotime($data['expire_at']),
            $this->logicalAnd(
                $this->lessThanOrEqual(strtotime("+100 days")),
                $this->greaterThan(strtotime("+100 days") - 30)
            )
        );
        // check addHashTags event listener
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags_petitions WHERE petition_id = ?', [$data['id']]);
        $this->assertCount($count, $hashTags);
        $this->assertCount($count, $data['cached_hash_tags']);
        // check root comment
        $body = $conn->fetchColumn('SELECT comment_body FROM comments WHERE petition_id = ?', [$data['id']]);
        $this->assertSame($data['petition_body'], $body);
        // check social activity
        $type = $conn->fetchColumn('SELECT type FROM social_activities WHERE group_id = ?', [$group->getId()]);
        $this->assertSame(SocialActivity::TYPE_GROUP_POST_CREATED, $type);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$data['id']]);
        $this->assertSame($data['petition_body'], $description);
    }

    public function testGetActivitiesOfDeletedMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
            LoadUserGroupFollowerTestData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $group = $repository->getReference('testfollowprivategroups');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkPetitionLimitPerMonth',
        ]);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.poll.micropetition_manager', $manager);
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{id}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'title' => $faker->sentence,
            'petition_body' => $faker->text."\n".implode(' ', $hashTags),
            'link' => $faker->url,
            'is_outsiders_sign' => $faker->boolean(),
            'type' => Petition::TYPE_OPEN_LETTER,
        ];
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM activities');
        $this->assertEquals(0, $count);
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$data['id']]);
        $this->assertSame($data['petition_body'], $description);
        $client->request('DELETE',
            '/api/v2/micro-petitions/'.$data['id'], [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM micropetitions WHERE id = ?', [$data['id']]);
        $this->assertEquals(0, $count);
        $client->request('GET',
            '/api/v2/activities', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(0, $data['payload']);
    }

    public function testCreateLongMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadGroupFollowerTestData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $group = $repository->getReference('testfollowprivategroups');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkPetitionLimitPerMonth',
        ]);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.poll.micropetition_manager', $manager);
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{id}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag2',
            '#powerlineHashTag2',
        ];
        $params = [
            'title' => $faker->sentence,
            'petition_body' => $faker->text."\n".implode(' ', $hashTags),
            'link' => $faker->url,
            'is_outsiders_sign' => $faker->boolean(),
            'type' => Petition::TYPE_LONG_PETITION,
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['title'], $data['title']);
        $this->assertSame($params['petition_body'], $data['petition_body']);
        $this->assertSame($params['link'], $data['link']);
        $this->assertSame($params['is_outsiders_sign'], $data['is_outsiders_sign']);
        $this->assertSame($params['type'], $data['type']);
        $this->assertSame($group->getId(), $data['group']['id']);
        $this->assertSame('userfollowtest1', $data['user']['username']);
        $this->assertSame(Petition::STATUS_USER, $data['publish_status']);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|PetitionManager
     */
    private function getPetitionManagerMock($methods = [])
    {
        $container = $this->client->getContainer();
        return $this->getMockBuilder(PetitionManager::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                $container->get('doctrine.orm.entity_manager'),
                $container->get('event_dispatcher')
            ])
            ->getMock();
    }
}
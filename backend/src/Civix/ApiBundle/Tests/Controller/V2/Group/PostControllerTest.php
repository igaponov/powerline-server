<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\DataCollector\RabbitMQDataCollector;
use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Service\PostManager;
use Civix\CoreBundle\Service\UserPetitionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class PostControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/groups/{group}/posts';

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

    public function testCreatePostWithErrors()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $manager = $this->getPetitionManagerMock(['checkPostLimitPerMonth']);
        $manager->expects($this->once())
            ->method('checkPostLimitPerMonth')
            ->will($this->returnValue(false));
        $this->client->getContainer()->set('civix_core.post_manager', $manager);
        $expectedErrors = [
            'Your limit of posts per month is reached.',
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

    /**
     * @QueryCount(18)
     * @todo move all events to background
     */
    public function testCreatePost()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $mentioned = $repository->getReference('user_4');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'body' => sprintf(
                'post text @%s %s',
                $mentioned->getUsername(),
                implode(' ', $hashTags)
            ),
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer user1'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['body'], $data['body']);
        $this->assertFalse($data['boosted']);
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(PostEvents::POST_CREATE, $msg->getEventName());
        $this->assertInstanceOf(PostEvent::class, $msg->getEvent());
    }

    public function testGetActivitiesOfDeletedUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadKarmaData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        $user = $repository->getReference('user_1');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkPostLimitPerMonth',
        ]);
        $manager->expects($this->once())
            ->method('checkPostLimitPerMonth')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.post_manager', $manager);
        $settings = $client->getContainer()->get('civix_core.settings');
        $settings->set('micropetition_expire_interval_0', 100);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'body' => $faker->text."\n".implode(' ', $hashTags),
        ];
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')
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
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE post_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $description);
        $count = $conn->fetchColumn(
            'SELECT COUNT(*) FROM karma WHERE user_id = ? AND type = ?',
            [$user->getId(), Karma::TYPE_CREATE_POST]
        );
        $this->assertEquals(1, $count);
        $client->request('DELETE',
            '/api/v2/posts/'.$data['id'], [], [],
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

    public function testGetPostsWithWrongCredentialsThrowException()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetPosts()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(4, $data['totalItems']);
        $this->assertCount(4, $data['payload']);
        foreach ($data['payload'] as $item) {
            $this->assertNotEmpty($item['user']);
            $this->assertNotEmpty($item['group']);
        }
    }

    public function testGetPostsByUser()
    {
        $repository = $this->loadFixtures([
            LoadPostData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $user = $repository->getReference('user_2');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, ['user' => $user->getId()], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
    }

    public function testGetPostsMarkedAsSpam()
    {
        $repository = $this->loadFixtures([
            LoadSpamPostData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, ['marked_as_spam' => 'true'], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UserPetitionManager
     */
    private function getPetitionManagerMock($methods = [])
    {
        $container = $this->client->getContainer();
        return $this->getMockBuilder(PostManager::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                $container->get('doctrine')->getManager(),
                $container->get('event_dispatcher')
            ])
            ->getMock();
    }
}
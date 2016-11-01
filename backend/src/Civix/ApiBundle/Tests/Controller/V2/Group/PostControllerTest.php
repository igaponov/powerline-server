<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\PostManager;
use Civix\CoreBundle\Service\UserPetitionManager;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
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

    public function testCreatePost()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_1');
        $mentioned = $repository->getReference('user_4');
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
            'body' => sprintf("post text @%s %s",
                $mentioned->getUsername(),
                implode(' ', $hashTags)
            ),
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['body'], $data['body']);
        $this->assertRegExp('{post text <a data-user-id="\d+">@user4</a> #testHashTag #powerlineHashTag}', $data['html_body']);
        $this->assertFalse($data['boosted']);
        // check addHashTags event listener
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags');
        $this->assertCount($count, $hashTags);
        $this->assertCount($count, $data['cached_hash_tags']);
        // check root comment
        $body = $conn->fetchColumn('SELECT comment_body FROM post_comments WHERE post_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $body);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE post_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $description);
        // check author subscription
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM post_subscribers WHERE post_id = ?', [$data['id']]);
        $this->assertEquals(1, $count);
        // check social activity
        $tester = new SocialActivityTester($client->getContainer()->get('doctrine.orm.entity_manager'));
        $tester->assertActivitiesCount(2);
        $tester->assertActivity(SocialActivity::TYPE_FOLLOW_POST_CREATED, null, $user->getId());
        $tester->assertActivity(SocialActivity::TYPE_POST_MENTIONED, $mentioned->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(2, $queue->hasMessageWithMethod('sendSocialActivity'));
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
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE post_id = ?', [$data['id']]);
        $this->assertSame($data['body'], $description);
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
                $container->get('doctrine.orm.entity_manager'),
                $container->get('event_dispatcher')
            ])
            ->getMock();
    }
}
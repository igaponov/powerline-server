<?php
namespace Civix\ApiBundle\Tests\Controller\V2_2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\DataCollector\RabbitMQDataCollector;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Service\PostManager;
use Civix\CoreBundle\Service\UserPetitionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class PostControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2.2/groups/{group}/posts';

    /**
     * @var null|Client
     */
    private $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadUserGroupData::class,
        ]);
    }

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown()
    {
        $this->client = null;
        parent::tearDown();
    }

    /**
     * @QueryCount(8)
     */
    public function testCreatePost()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $mentioned = $repository->getReference('user_4');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->enableProfiler();
        $settings = $client->getContainer()->get('civix_core.settings');
        $interval = 100;
        $settings->set('micropetition_expire_interval_0', $interval);
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
        $client->request('POST', $uri, [], [], ['HTTP_Authorization' => 'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(10, $data);
        $this->assertNotEmpty($data['id']);
        $this->assertSame($params['body'], $data['body']);
        $this->assertSame(sprintf(
            'post text <a data-user-id="%d">@%s</a> %s',
            $mentioned->getId(),
            $mentioned->getUsername(),
            implode(' ', $hashTags)
        ), $data['html_body']);
        $this->assertNotEmpty($data['created_at']);
        $this->assertNotEmpty($data['expired_at']);
        $this->assertSame($interval, $data['user_expire_interval']);
        $this->assertFalse($data['boosted']);
        $this->assertFalse($data['supporters_were_invited']);
        $this->assertFalse($data['automatic_boost']);
        $this->assertRegExp('/[\w\d]\.png/', $data['facebook_thumbnail']);
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(PostEvents::POST_CREATE, $msg->getEventName());
        $this->assertInstanceOf(PostEvent::class, $msg->getEvent());
    }

    /**
     * @QueryCount(2)
     */
    public function testCreatePostWithError()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $mentioned = $repository->getReference('user_4');
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $manager = $this->getPetitionManagerMock(['checkPostLimitPerMonth']);
        $manager->expects($this->once())
            ->method('checkPostLimitPerMonth')
            ->will($this->returnValue(false));
        $this->client->getContainer()->set('civix_core.post_manager', $manager);
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
        $client->enableProfiler();
        $client->request('POST', $uri, [], [], ['HTTP_Authorization' => 'Bearer user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Your limit of posts per month is reached.', $data['message']);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UserPetitionManager
     */
    private function getPetitionManagerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
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
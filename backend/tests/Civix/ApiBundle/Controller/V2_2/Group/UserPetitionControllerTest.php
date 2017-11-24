<?php
namespace Tests\Civix\ApiBundle\Controller\V2_2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\DataCollector\RabbitMQDataCollector;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Service\UserPetitionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamUserPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Faker\Factory;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPetitionControllerTest extends WebTestCase
{
    private const API_ENDPOINT = '/api/v2.2/groups/{group}/user-petitions';

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
            LoadSpamUserPetitionData::class,
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
     * @QueryCount("2")
     */
    public function testCreateUserPetitionWithErrors()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $manager = $this->getPetitionManagerMock(['checkPetitionLimitPerMonth']);
        $manager->expects($this->once())
            ->method('checkPetitionLimitPerMonth')
            ->will($this->returnValue(false));
        $this->client->getContainer()->set('civix_core.user_petition_manager', $manager);
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization' => 'Bearer user1']);
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Your limit of petitions per month is reached.', $data['message']);
    }

    /**
     * @QueryCount("7")
     */
    public function testCreateUserPetition()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $faker = Factory::create();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->enableProfiler();
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'title' => $faker->sentence,
            'body' => $faker->text."\n".implode(' ', $hashTags),
            'is_outsiders_sign' => true,
            'organization_needed' => true,
        ];
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization' => 'Bearer user1'],
            json_encode(array_merge([
                'image' => base64_encode(file_get_contents(__DIR__.'/../../../../../data/image.png')),
            ],
                $params
            ))
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(13, $data);
        $this->assertNotEmpty($data['id']);
        foreach ($params as $key => $param) {
            $this->assertSame($param, $data[$key]);
        }
        $this->assertSame($params['body'], $data['html_body']);
        $this->assertFalse($data['boosted']);
        $this->assertNotEmpty($data['created_at']);
        $this->assertNotEmpty($data['expired_at']);
        $this->assertNotEmpty($data['image']);
        $this->assertFalse($data['supporters_were_invited']);
        $this->assertTrue($data['automatic_boost']);
        $this->assertRegExp('/[\w\d]\.png/', $data['facebook_thumbnail']);
        $activity = $this->getContainer()->get('database_connection')
            ->fetchAssoc('SELECT * FROM activities');
        $this->assertSame($data['body'], $activity['description']);
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(UserPetitionEvents::PETITION_CREATE, $msg->getEventName());
        $this->assertInstanceOf(UserPetitionEvent::class, $msg->getEvent());
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('image_petition_fs'));
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UserPetitionManager
     */
    private function getPetitionManagerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        $container = $this->client->getContainer();
        return $this->getMockBuilder(UserPetitionManager::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                $container->get('doctrine')->getManager(),
                $container->get('event_dispatcher')
            ])
            ->getMock();
    }
}
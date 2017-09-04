<?php
namespace Tests\Civix\ApiBundle\Controller\V2\Group;

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
    const API_ENDPOINT = '/api/v2/groups/{group}/user-petitions';

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

    public function testGetPetitionsWithWrongCredentialsThrowException()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, [], [], ['HTTP_Authorization' => 'Bearer user2']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetPetitions()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, [], [], ['HTTP_Authorization' => 'Bearer user1']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(4, $data['totalItems']);
        /** @var array $payload */
        $payload = $data['payload'];
        $this->assertCount(4, $payload);
        foreach ($payload as $item) {
            $this->assertNotEmpty($item['user']);
            $this->assertNotEmpty($item['group']);
        }
    }

    public function testGetPetitionsByUser()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $user = $repository->getReference('user_2');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, ['user' => $user->getId()], [], ['HTTP_Authorization' => 'Bearer user1']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
    }

    public function testGetPetitionsMarkedAsSpam()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET',
            $uri, ['marked_as_spam' => 'true'], [], ['HTTP_Authorization' => 'Bearer user1']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
    }

    public function testCreateUserPetitionWithErrors()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
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
        $client->request('POST', $uri, [], [], ['HTTP_Authorization' => 'Bearer user1']);
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
     * @QueryCount("14")
     */
    public function testCreateUserPetition()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
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
        $client->enableProfiler();
        $client->request('POST',
            $uri, [], [], ['HTTP_Authorization' => 'Bearer user1'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['body'], $data['body']);
        $this->assertSame($params['is_outsiders_sign'], $data['is_outsiders_sign']);
        $this->assertFalse($data['boosted']);
        $this->assertFalse($data['organization_needed']);
        /** @var RabbitMQDataCollector $collector */
        $collector = $client->getProfile()->getCollector('rabbit_mq');
        $data = $collector->getData();
        $this->assertCount(1, $data);
        $msg = unserialize($data[0]['msg']);
        $this->assertSame(UserPetitionEvents::PETITION_CREATE, $msg->getEventName());
        $this->assertInstanceOf(UserPetitionEvent::class, $msg->getEvent());
    }

    public function testCreateOpenLetter()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
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
            $uri, [], [], ['HTTP_Authorization' => 'Bearer user1'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['title'], $data['title']);
        $this->assertSame($params['body'], $data['body']);
        $this->assertSame($params['is_outsiders_sign'], $data['is_outsiders_sign']);
        $this->assertSame($params['organization_needed'], $data['organization_needed']);
        $this->assertFalse($data['boosted']);
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
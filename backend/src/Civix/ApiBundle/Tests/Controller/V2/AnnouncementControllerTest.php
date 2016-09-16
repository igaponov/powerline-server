<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\EventListener\PushSenderSubscriber;
use Civix\CoreBundle\Model\Subscription\PackageLimitState;
use Civix\CoreBundle\Service\Subscription\PackageHandler;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class AnnouncementControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/announcements';

    /**
     * @var null|Client
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

    public function testGetAnnouncementsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
        $announcement = $repository->getReference('announcement_jb_3');
        $this->assertEquals($announcement->getContent(), $data['payload'][0]['content_parsed']);
    }

    public function testGetAnnouncementsWithStartParameterIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $date = new \DateTime('-2 months');
        $client->request('GET', self::API_ENDPOINT, ['start' => $date->format('Y-m-d H:i:s')], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['payload']);
        $announcement1 = $repository->getReference('announcement_jb_2');
        $announcement2 = $repository->getReference('announcement_jb_3');
        foreach ($data['payload'] as $item) {
            $this->assertThat(
                $item['content_parsed'],
                $this->logicalOr($announcement1->getContent(), $announcement2->getContent())
            );
        }
    }

    public function testGetAnnouncementsIsEmpty()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="testuserbookmark1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }

    public function testGetAnnouncementWithWrongCredentialsReturnsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        /** @var Announcement $announcement */
        $announcement = $repository->getReference('announcement_private_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForGetRequest
     */
    public function testGetAnnouncementIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $announcement = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
    }

    public function getValidAnnouncementCredentialsForGetRequest()
    {
        return [
            'owner' => ['user1', 'announcement_group_1'],
            'manager' => ['user3', 'announcement_private_1'],
            'member' => ['user3', 'announcement_topsecret_1'],
        ];
    }

    /**
     * @param array $params
     * @param array $errors
     * @dataProvider getInvalidParams
     */
    public function testUpdateAnnouncementReturnsErrors($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_group_1');
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Token'=>'user1'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $children = $data['errors']['children'];
        foreach ($errors as $child => $error) {
            $this->assertEquals([$error], $children[$child]['errors']);
        }
    }

    public function getInvalidParams()
    {
        return [
            'empty content' => [
                [
                    'content' => '',
                ],
                [
                    'content' => 'This value should not be blank.',
                ]
            ],
        ];
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidAnnouncementCredentialsForUpdateRequest
     */
    public function testUpdateAnnouncementWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference($reference);
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function getInvalidAnnouncementCredentialsForUpdateRequest()
    {
        return [
            'outlier' => ['user1', 'announcement_private_1'],
            'member' => ['user3', 'announcement_topsecret_1'],
        ];
    }

    public function testUpdatePublishedAnnouncementReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_group_2');
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(["Announcement is already published"], $data['errors']['errors']);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForUpdateRequest
     */
    public function testUpdateAnnouncementIsOk($user, $reference)
    {
        $faker = Factory::create();
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $params = [
            'content' => $faker->sentence,
        ];
        $announcement = $repository->getReference($reference);
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
        $this->assertSame($params['content'], $data['content_parsed']);
    }

    public function getValidAnnouncementCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user1', 'announcement_group_1'],
            'manager' => ['user3', 'announcement_group_1'],
        ];
    }

    public function testUpdateAnnouncementWithExceededLimitReturnsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(2));
        $announcement = $repository->getReference('announcement_group_1');
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPublishPublishedAnnouncementReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock());
        $announcement = $repository->getReference('announcement_group_2');
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(["Announcement is already published"], $data['errors']['errors']);
    }

    public function testPublishAnnouncementIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $announcement = $repository->getReference('announcement_group_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock());
        $client->getContainer()->set('civix_core.event_listener.push_sender_subscriber', $this->getPushSenderSubscriberMock());
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
        $this->assertNotNull($data['published_at']);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidAnnouncementCredentialsForUpdateRequest
     */
    public function testDeleteAnnouncementWithWrongCredentialsReturnsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference($reference);
        $client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeletePublishedAnnouncementReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_group_2');
        $client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(["Announcement is already published"], $data['errors']['errors']);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForUpdateRequest
     */
    public function testDeleteAnnouncementIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $announcement = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    private function getPackageHandlerMock($currentValue = 1, $limitValue = 2)
    {
        $service = $this->getMockBuilder(PackageHandler::class)
            ->setMethods(['getPackageStateForAnnouncement'])
            ->disableOriginalConstructor()
            ->getMock();
        $packageLimitState = new PackageLimitState();
        $packageLimitState->setCurrentValue($currentValue);
        $packageLimitState->setLimitValue($limitValue);
        $service->expects($this->any())
            ->method('getPackageStateForAnnouncement')
            ->with($this->isInstanceOf(Group::class))
            ->will($this->returnValue($packageLimitState));

        return $service;
    }

    private function getPushSenderSubscriberMock()
    {
        $service = $this->getMockBuilder(PushSenderSubscriber::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('sendAnnouncementPush');

        return $service;
    }
}
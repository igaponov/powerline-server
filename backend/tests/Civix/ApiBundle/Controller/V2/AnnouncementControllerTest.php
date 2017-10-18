<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Model\Subscription\PackageLimitState;
use Civix\CoreBundle\Service\Subscription\PackageHandler;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupAnnouncementReadData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupRepresentativesData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class AnnouncementControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/announcements';

    /**
     * @var null|Client
     */
    private $client;

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
        /** @var Announcement $announcement */
        $announcement = $repository->getReference('announcement_jb_3');
        $item = $data['payload'][0];
        $this->assertEquals($announcement->getContent(), $item['content_parsed']);
        $this->assertFalse($item['is_read']);
        $this->assertContains('/'.$announcement->getImage()->getName().'?', $item['image']);
    }

    public function testGetGroupAnnouncementsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupOwnerData::class,
            LoadGroupAnnouncementReadData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['page']);
        $this->assertSame(20, $data['items']);
        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['payload']);
        $this->assertArrayHasKey('official_name', $data['payload'][0]['group']);
        $this->assertArrayHasKey('avatar_file_path', $data['payload'][0]['group']);
        $announcement = $repository->getReference('announcement_group_3');
        $this->assertEquals($announcement->getContent(), $data['payload'][0]['content_parsed']);
        $this->assertTrue($data['payload'][0]['is_read']);
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
        /** @var array $payload */
        $payload = $data['payload'];
        $this->assertCount(2, $payload);
        $announcement1 = $repository->getReference('announcement_jb_2');
        $announcement2 = $repository->getReference('announcement_jb_3');
        foreach ($payload as $item) {
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

    public function testGetGroupAnnouncementWithWrongCredentialsReturnsException()
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

    public function testGetRepresentativeAnnouncementWithWrongCredentialsReturnsException()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        /** @var Announcement $announcement */
        $announcement = $repository->getReference('announcement_jb_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForGetRequest
     */
    public function testGetGroupAnnouncementIsOk($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures(
            array_merge([LoadGroupAnnouncementData::class], $fixtures)
        )->getReferenceRepository();
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
            'owner' => [[], 'user1', 'announcement_group_1'],
            'manager' => [[LoadGroupManagerData::class], 'user3', 'announcement_private_1'],
            'member' => [[LoadUserGroupData::class], 'user3', 'announcement_topsecret_1'],
            'representative' => [[LoadGroupRepresentativesData::class], 'user3', 'announcement_group_1'],
        ];
    }

    public function testGetRepresentativeAnnouncementIsOk()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $announcement = $repository->getReference('announcement_jb_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
    }

    /**
     * @param array $params
     * @param array $errors
     * @dataProvider getInvalidParams
     */
    public function testUpdateGroupAnnouncementReturnsErrors($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_group_1');
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Token'=>'user1'], json_encode($params));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    /**
     * @param array $params
     * @param array $errors
     * @dataProvider getInvalidParams
     */
    public function testUpdateRepresentativeAnnouncementReturnsErrors($params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_jb_1');
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Token'=>'user1'], json_encode($params));
        $this->assertResponseHasErrors($client->getResponse(), $errors);
    }

    public function getInvalidParams()
    {
        return [
            'empty content' => [
                [
                    'content' => '',
                ],
                [
                    'The announcement should not be blank.',
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
    public function testUpdateGroupAnnouncementWithWrongCredentialsReturnsException($user, $reference)
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

    public function testUpdateRepresentativeAnnouncementWithWrongCredentialsReturnsException()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_jb_1');
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdatePublishedGroupAnnouncementReturnsError()
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
        $this->assertSame(['Announcement is already published'], $data['errors']['errors']);
    }

    public function testUpdatePublishedRepresentativeAnnouncementReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_jb_2');
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['Announcement is already published'], $data['errors']['errors']);
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForUpdateRequest
     */
    public function testUpdateGroupAnnouncementIsOk($fixtures, $user, $reference)
    {
        $faker = Factory::create();
        $repository = $this->loadFixtures(
            array_merge([LoadGroupAnnouncementData::class], $fixtures)
        )->getReferenceRepository();
        $params = [
            'content' => $faker->sentence,
            'image' => base64_encode(file_get_contents(__DIR__.'/../../../../data/image2.png')),
        ];
        $announcement = $repository->getReference($reference);
        $image = $announcement->getImage()->getName();
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
        $this->assertSame($params['content'], $data['content_parsed']);
        $this->assertNotSame($image, $data['image']);
    }

    public function getValidAnnouncementCredentialsForUpdateRequest()
    {
        return [
            'owner' => [[], 'user1', 'announcement_group_1'],
            'manager' => [[LoadGroupManagerData::class], 'user3', 'announcement_group_1'],
            'representative' => [[LoadGroupRepresentativesData::class], 'user3', 'announcement_group_1'],
        ];
    }

    public function testUpdateRepresentativeAnnouncementIsOk()
    {
        $faker = Factory::create();
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $params = [
            'content' => $faker->sentence,
        ];
        $announcement = $repository->getReference('announcement_jb_1');
        $client = $this->client;
        $client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
        $this->assertSame($params['content'], $data['content_parsed']);
    }

    public function testPublishGroupAnnouncementWithExceededLimitReturnsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(Group::class, 2));
        $announcement = $repository->getReference('announcement_group_1');
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPublishRepresentativeAnnouncementWithExceededLimitReturnsException()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(Representative::class, 2));
        $announcement = $repository->getReference('announcement_jb_1');
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testPublishPublishedGroupAnnouncementReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(Group::class));
        $announcement = $repository->getReference('announcement_group_2');
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['Announcement is already published'], $data['errors']['errors']);
    }

    public function testPublishPublishedRepresentativeAnnouncementReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(Representative::class));
        $announcement = $repository->getReference('announcement_jb_2');
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], '{}');
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['Announcement is already published'], $data['errors']['errors']);
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForUpdateRequest
     */
    public function testPublishGroupAnnouncementIsOk($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures(
            array_merge([LoadGroupAnnouncementData::class], $fixtures)
        )->getReferenceRepository();
        /** @var Announcement\GroupAnnouncement $announcement */
        $announcement = $repository->getReference($reference);
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(Group::class));
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
        $this->assertNotNull($data['published_at']);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendPublishedGroupAnnouncementPush', [$announcement->getGroup()->getId(), $announcement->getId()]));
    }

    public function testPublishRepresentativeAnnouncementIsOk()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        /** @var Announcement\RepresentativeAnnouncement $announcement */
        $announcement = $repository->getReference('announcement_jb_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(Representative::class));
        $client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($announcement->getId(), $data['id']);
        $this->assertNotNull($data['published_at']);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendPublishedRepresentativeAnnouncementPush', [$announcement->getRepresentative()->getId(), $announcement->getId()]));
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidAnnouncementCredentialsForUpdateRequest
     */
    public function testDeleteGroupAnnouncementWithWrongCredentialsReturnsException($user, $reference)
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

    public function testDeleteRepresentativeAnnouncementWithWrongCredentialsReturnsException()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_jb_1');
        $client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user2"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeletePublishedGroupAnnouncementReturnsError()
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
        $this->assertSame(['Announcement is already published'], $data['errors']['errors']);
    }

    public function testDeletePublishedRepresentativeAnnouncementReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $announcement = $repository->getReference('announcement_jb_2');
        $client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['Announcement is already published'], $data['errors']['errors']);
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidAnnouncementCredentialsForUpdateRequest
     */
    public function testDeleteGroupAnnouncementIsOk($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures(
            array_merge([LoadGroupAnnouncementData::class], $fixtures)
        )->getReferenceRepository();
        $announcement = $repository->getReference($reference);
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteRepresentativeAnnouncementIsOk()
    {
        $repository = $this->loadFixtures([
            LoadRepresentativeAnnouncementData::class,
        ])->getReferenceRepository();
        $announcement = $repository->getReference('announcement_jb_1');
        $client = $this->client;
        $client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    public function testPatchGroupAnnouncementsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $announcement1 = $repository->getReference('announcement_group_1');
        $announcement3 = $repository->getReference('announcement_group_3');
        $user = $repository->getReference('user_1');
        $params = ['announcements' => [$announcement1->getId(), $announcement3->getId()], 'read' => true];
        $client = $this->client;
        $client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        foreach ($data as $item) {
            $this->assertThat($item['id'], $this->logicalOr($announcement1->getId(), $announcement3->getId()));
            $this->assertTrue($item['is_read']);
        }
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM announcement_read WHERE user_id = ?', [$user->getId()]);
        $this->assertEquals(2, $count);
        $sum = $conn->fetchColumn('SELECT SUM(points) FROM karma WHERE user_id = ? AND type = ?', [$user->getId(), Karma::TYPE_VIEW_ANNOUNCEMENT]);
        $this->assertEquals(4, $sum);
    }

    public function testPatchGroupAnnouncementsReadFalseIsOk()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
        ])->getReferenceRepository();
        $announcement1 = $repository->getReference('announcement_group_1');
        $announcement3 = $repository->getReference('announcement_group_3');
        $user = $repository->getReference('user_1');
        $params = ['announcements' => [$announcement1->getId(), $announcement3->getId()], 'read' => false];
        $client = $this->client;
        $client->request('PATCH', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var array $data */
        $data = json_decode($response->getContent(), true);
        foreach ($data as $item) {
            $this->assertThat($item['id'], $this->logicalOr($announcement1->getId(), $announcement3->getId()));
            $this->assertFalse($item['is_read']);
        }
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $sum = $conn->fetchColumn('SELECT SUM(points) FROM karma WHERE user_id = ? AND type = ?', [$user->getId(), Karma::TYPE_VIEW_ANNOUNCEMENT]);
        $this->assertEquals(0, $sum);
    }

    private function getPackageHandlerMock($class, $currentValue = 1, $limitValue = 2)
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
            ->with($this->isInstanceOf($class))
            ->will($this->returnValue($packageLimitState));

        return $service;
    }
}
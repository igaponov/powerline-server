<?php
namespace Tests\Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Report\PetitionResponseReport;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\UserPetitionManager;
use Civix\CoreBundle\Test\SocialActivityTester;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSpamUserPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadPetitionResponseReportData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadUserReportData;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPetitionControllerTest extends WebTestCase
{
    private const API_ENDPOINT = '/api/v2/user-petitions';

    /**
     * @var null|Client
     */
    private $client;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    protected function tearDown(): void
    {
        $this->client = null;
        parent::tearDown();
    }

    public function testGetPetitions()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadUserPetitionData::class,
            LoadUserPetitionSignatureData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $signature = $repository->getReference('petition_answer_1');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(6, $data['totalItems']);
        /** @var array $payload */
        $payload = $data['payload'];
        $this->assertCount(6, $payload);
        foreach ($payload as $item) {
            if ($petition->getId() === $item['id']) {
                $this->assertCount(1, $item['answers']);
                $this->assertEquals($signature->getOptionId(), $item['answers'][0]['option_id']);
                $this->assertArrayHasKey('html_body', $item);
                $this->assertContains($petition->getImage()->getName(), $item['image']);
            }
        }
    }

    public function testGetPetitionsByTag()
    {
        $this->loadFixtures([
            LoadUserGroupData::class,
            LoadUserPetitionData::class,
        ]);
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, ['tag' => 'hash_tag_name'], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }

    public function testGetUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($petition->getBody(), $data['body']);
        $this->assertArrayHasKey('group_id', $data);
        $this->assertNotEmpty($data['group_id']);
        $this->assertSame($petition->isSupportersWereInvited(), $data['supporters_were_invited']);
        $this->assertContains($petition->getImage()->getName(), $data['image']);
    }

    public function testGetDeletedUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_7');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateMicropetitionAccessDenied()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateUserPetitionReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $expectedErrors = [
            'body' => 'This value should not be blank.',
        ];
        $client = $this->client;
        $params = [
            'body' => '',
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $errors = $data['errors'];
        foreach ($expectedErrors as $child => $error) {
            if (\is_int($child)) {
                $this->assertContains($error, $errors['errors']);
            } elseif ($error) {
                $this->assertContains($error, $errors['children'][$child]['errors']);
            } else {
                $this->assertEmpty($errors['children'][$child]);
            }
        }
    }

    public function testUpdateUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $image = $petition->getImage()->getName();
        $client = $this->client;
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'body' => $faker->text."\n".implode(' ', $hashTags),
            'image' => base64_encode(file_get_contents(__DIR__.'/../../../../data/image.png')),
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['body'], $data['body']);
        $this->assertNotSame($image, $data['image']);
        $storage = $client->getContainer()->get('civix_core.storage.array');
        $this->assertCount(1, $storage->getFiles('image_petition_fs'));
    }

    /**
     * @param $fixtures
     * @param $user
     * @param $reference
     * @dataProvider getValidPetitionCredentialsForBoostRequest
     */
    public function testBoostUserPetition($fixtures, $user, $reference)
    {
        $repository = $this->loadFixtures($fixtures)->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference($reference);
        $this->assertFalse($petition->isBoosted());
        $client = $this->client;
        $client->request('PATCH',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['boosted']);
        // check activity
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')->getConnection();
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$petition->getId()]);
        $this->assertSame($petition->getBody(), $description);
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(1, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendBoostedPetitionPush', [$petition->getGroup()->getId(), $petition->getId()]));
    }

    public function testBoostUserPetitionWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_2');
        $this->assertFalse($petition->isBoosted());
        $client = $this->client;
        $client->request('PATCH',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user4"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteUserPetitionAccessDenied()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_4');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')
            ->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM user_petitions WHERE id = ?', [$petition->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testDeleteUserPetitionByGroupManager()
    {
        $repository = $this->loadFixtures([
            LoadSpamUserPetitionData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_4');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine')
            ->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM user_petitions WHERE id = ?', [$petition->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testSignUserPetitionThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_6');
        $client = $this->client;
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/sign', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testSignUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $manager = $this->getPetitionManagerMock(['checkIfNeedBoost']);
        $manager->expects($this->once())
            ->method('checkIfNeedBoost')
            ->willReturn(true);
        $client->getContainer()->set('civix_core.user_petition_manager', $manager);
        /** @var User $user */
        $user = $repository->getReference('user_2');
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_2');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/sign', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var EntityManager $em */
        $em = $client->getContainer()
            ->get('doctrine')->getManager();
        $conn = $em->getConnection();
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$petition->getId()]);
        $this->assertSame($petition->getBody(), $description);
        $this->assertTrue($petition->isBoosted());
        $tester = new SocialActivityTester($em);
        $tester->assertActivitiesCount(1);
        $tester->assertActivity(SocialActivity::TYPE_OWN_USER_PETITION_SIGNED, $petition->getUser()->getId());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(2, $queue->count());
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendSocialActivity'));
        $this->assertEquals(1, $queue->hasMessageWithMethod('sendBoostedPetitionPush', [$petition->getGroup()->getId(), $petition->getId()]));
        $report = $em->getRepository(PetitionResponseReport::class)
            ->getPetitionResponseReport($user, $petition);
        $this->assertNotNull($report);
    }

    public function testSignUserPetitionWithoutAutomaticBoost()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $manager = $this->getPetitionManagerMock(['checkIfNeedBoost']);
        $manager->expects($this->once())
            ->method('checkIfNeedBoost')
            ->willReturn(true);
        $client->getContainer()->set('civix_core.user_petition_manager', $manager);
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_4');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/sign', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $queue = $client->getContainer()->get('civix_core.mock_queue_task');
        $this->assertEquals(0, $queue->count());
    }

    public function testUpdateAnswer()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionSignatureData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/sign', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    public function testUnsignUserPetition()
    {
        $repository = $this->loadFixtures([
            LoadPetitionResponseReportData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId().'/sign', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var EntityManager $em */
        $em = $client->getContainer()
            ->get('doctrine')->getManager();
        $conn = $em->getConnection();
        // check social activity
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM user_petition_signatures WHERE petition_id = ? AND user_id = ?', [$petition->getId(), $user->getId()]);
        $this->assertSame(0, $count);
        $report = $em->getRepository(PetitionResponseReport::class)
            ->getPetitionResponseReport($user, $petition);
        $this->assertNull($report);
    }

    public function testMarkPetitionAsSpam()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_2');
        $user = $repository->getReference('user_2');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/spam', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM spam_user_petitions WHERE userpetition_id = ? AND user_id = ?', [$petition->getId(), $user->getId()]);
        $this->assertEquals(1, $count);
    }

    public function testUnmarkPetitionAsSpam()
    {
        $repository = $this->loadFixtures([
            LoadSpamUserPetitionData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        $user = $repository->getReference('user_2');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId().'/spam', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user3"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $conn = $client->getContainer()->get('doctrine.dbal.default_connection');
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM spam_user_petitions WHERE userpetition_id = ? AND user_id = ?', [$petition->getId(), $user->getId()]);
        $this->assertEquals(0, $count);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPetitionCredentialsForGetResponsesRequest
     */
    public function testGetPetitionResponsesWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionSignatureData::class,
        ])->getReferenceRepository();
        $petition = $repository->getReference($reference);
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$petition->getId().'/responses', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetPetitionResponsesIsOk()
    {
        $repository = $this->loadFixtures([
            LoadUserReportData::class,
            LoadPetitionResponseReportData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
        /** @var User[] $users */
        $users = [
            $repository->getReference('user_1'),
            $repository->getReference('user_2'),
            $repository->getReference('user_3'),
        ];
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT.'/'.$petition->getId().'/responses', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        foreach ($users as $k => $user) {
            $this->assertEquals($user->getLatitude(), $data[$k]['latitude']);
            $this->assertEquals($user->getLongitude(), $data[$k]['longitude']);
            if (\in_array($user->getUsername(), ['user1', 'user3'], true)) {
                $this->assertSame('US', $data[$k]['country']);
                $this->assertSame('NY', $data[$k]['state']);
                $this->assertSame('New York', $data[$k]['locality']);
                $this->assertSame(['United States', 'New York'], $data[$k]['districts']);
                $this->assertNotEmpty($data[$k]['representatives']);
            } else {
                $this->assertEmpty($data[$k]['country']);
                $this->assertEmpty($data[$k]['state']);
                $this->assertEmpty($data[$k]['locality']);
                $this->assertEmpty($data[$k]['districts']);
                $this->assertEmpty($data[$k]['representatives']);
            }
        }
    }

    public function getValidPetitionCredentialsForBoostRequest()
    {
        return [
            'creator' => [[LoadUserPetitionData::class], 'user1', 'user_petition_2'],
            'owner' => [[LoadUserPetitionData::class], 'user2', 'user_petition_2'],
            'manager' => [[LoadUserPetitionData::class, LoadGroupManagerData::class], 'user3', 'user_petition_2'],
        ];
    }

    public function getInvalidPetitionCredentialsForGetResponsesRequest()
    {
        return [
            'manager' => ['user2', 'user_petition_1'],
            'member' => ['user4', 'user_petition_1'],
            'outlier' => ['user1', 'user_petition_4'],
        ];
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UserPetitionManager
     */
    private function getPetitionManagerMock(array $methods = [])
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
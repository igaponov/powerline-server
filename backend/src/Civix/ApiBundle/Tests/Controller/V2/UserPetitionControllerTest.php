<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\PushTask;
use Civix\CoreBundle\Service\UserPetitionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class UserPetitionControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/user-petitions';

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

    public function testGetPetitions()
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadUserPetitionData::class,
            LoadUserPetitionSignatureData::class,
        ])->getReferenceRepository();
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
        $this->assertCount(6, $data['payload']);
        foreach ($data['payload'] as $item) {
            if ($petition->getId() == $item['id']) {
                $this->assertCount(1, $item['answers']);
                $this->assertEquals($signature->getOptionId(), $item['answers'][0]['option_id']);
                $this->assertArrayHasKey('html_body', $item);
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
            if (is_int($child)) {
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
        $client = $this->client;
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'body' => $faker->text."\n".implode(' ', $hashTags),
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
        // check addHashTags event listener
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM hash_tags_petitions WHERE petition_id = ?', [$data['id']]);
        $this->assertCount($count, $hashTags);
        $this->assertCount($count, $data['cached_hash_tags']);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$data['id']]);
        $this->assertSame($data['petition_body'], $description);
    }

    public function testDeleteUserPetitionAccessDenied()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_1');
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
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
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
        $manager = $this->getPetitionManagerMock([
            'checkIfNeedBoost',
        ]);
        $manager->expects($this->once())
            ->method('checkIfNeedBoost')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.user_petition_manager', $manager);
        $service = $this->getMock(PushTask::class, ['addToQueue'], [], '', false);
        $service->expects($this->once())->method('addToQueue')->with('sendGroupPetitionPush');
        $client->getContainer()->set('civix_core.push_task', $service);
        /** @var UserPetition $petition */
        $petition = $repository->getReference('user_petition_2');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/sign', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM social_activities WHERE type = ?', [SocialActivity::TYPE_OWN_POST_VOTED]);
        $this->assertSame(1, $count);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$petition->getId()]);
        $this->assertSame($petition->getBody(), $description);
        $this->assertTrue($petition->isBoosted());
    }

    public function testUpdateAnswer()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionSignatureData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Petition $petition */
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
            LoadUserPetitionSignatureData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Petition $petition */
        $petition = $repository->getReference('user_petition_1');
        $user = $repository->getReference('user_2');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId().'/sign', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="user2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        // check social activity
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM user_petition_signatures WHERE petition_id = ? AND user_id = ?', [$petition->getId(), $user->getId()]);
        $this->assertSame(0, $count);
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
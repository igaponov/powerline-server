<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Service\PushTask;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionAnswerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadMicropetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\DBAL\Connection;
use Faker\Factory;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class MicropetitionControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/micro-petitions';

    /**
     * @var null|Client
     */
    private $client = null;

    public function setUp()
    {
        // Creates a initial client
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
    }

    public function testGetMicropetitions()
    {
        $this->loadFixtures([
            LoadUserGroupData::class,
            LoadMicropetitionData::class,
        ]);
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(6, $data['totalItems']);
        $this->assertCount(6, $data['payload']);
    }

    public function testGetMicropetitionsByTag()
    {
        $this->loadFixtures([
            LoadUserGroupData::class,
            LoadMicropetitionData::class,
        ]);
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT, ['tag' => 'hash_tag_name'], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['payload']);
    }

    public function testGetMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($petition->getPetitionBody(), $data['petition_body']);
    }

    public function testGetDeletedMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_7');
        $client = $this->client;
        $client->request('GET',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']
        );
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateMicropetitionAccessDenied()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $client = $this->client;
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"'],
            json_encode([])
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateMicropetitionReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $expectedErrors = [
            'petition_body' => 'This value should not be blank.',
            'type' => 'The value you selected is not a valid choice.',
        ];
        $client = $this->client;
        $params = [
            'petition_body' => '',
            'type' => 'invalid type',
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'],
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

    public function testUpdateMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        $faker = Factory::create();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $client = $this->client;
        $hashTags = [
            '#testHashTag',
            '#powerlineHashTag',
        ];
        $params = [
            'petition_body' => $faker->text."\n".implode(' ', $hashTags),
        ];
        $client->request('PUT',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'],
            json_encode($params)
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($params['petition_body'], $data['petition_body']);
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

    public function testDeleteMicropetitionAccessDenied()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $client = $this->client;
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId(), [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = $conn->fetchColumn('SELECT COUNT(*) FROM micropetitions WHERE id = ?', [$petition->getId()]);
        $this->assertEquals(0, $count);
    }

    public function testSignMicropetitionReturnsErrors()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_6');
        $expectedErrors = [
            'You could not answer to expired micropetition.',
            'You could not answer to your micropetition.',
            'option' => 'This value should not be blank.',
        ];
        $client = $this->client;
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/answer', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest3"']
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

    public function testSignMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        $manager = $this->getPetitionManagerMock([
            'checkIfNeedPublish',
        ]);
        $manager->expects($this->once())
            ->method('checkIfNeedPublish')
            ->will($this->returnValue(true));
        $client->getContainer()->set('civix_core.poll.micropetition_manager', $manager);
        $service = $this->getMock(PushTask::class, ['addToQueue'], [], '', false);
        $service->expects($this->once())->method('addToQueue')->with('sendGroupPetitionPush');
        $client->getContainer()->set('civix_core.push_task', $service);
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_2');
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/answer', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"'],
            json_encode(['option' => 'upvote'])
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(Petition::OPTION_ID_UPVOTE, $data['option_id']);
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM social_activities WHERE type = ?', [SocialActivity::TYPE_OWN_POST_VOTED]);
        $this->assertSame(1, $count);
        // check activity
        $description = $conn->fetchColumn('SELECT description FROM activities WHERE petition_id = ?', [$petition->getId()]);
        $this->assertSame($petition->getPetitionBody(), $description);
    }

    public function testUpdateAnswer()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionAnswerData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $user = $repository->getReference('userfollowtest3');
        $answer = $conn->fetchAssoc('SELECT id, option_id FROM micropetitions_answers WHERE petition_id = ? AND user_id = ?', [$petition->getId(), $user->getId()]);
        $client->request('POST',
            self::API_ENDPOINT.'/'.$petition->getId().'/answer', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest3"'],
            json_encode(['option' => 'downvote'])
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(Petition::OPTION_ID_DOWNVOTE, $data['option_id']);
        $this->assertEquals($answer['id'], $data['id']);
        $this->assertNotEquals($answer['option_id'], $data['option_id']);
        // check social activity
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM social_activities WHERE type = ?', [SocialActivity::TYPE_OWN_POST_VOTED]);
        $this->assertSame(1, $count);
    }

    public function testUnsignMicropetition()
    {
        $repository = $this->loadFixtures([
            LoadMicropetitionAnswerData::class,
        ])->getReferenceRepository();
        $client = $this->client;
        /** @var Petition $petition */
        $petition = $repository->getReference('micropetition_1');
        $user = $repository->getReference('userfollowtest2');
        $client->request('DELETE',
            self::API_ENDPOINT.'/'.$petition->getId().'/answer', [], [],
            ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest2"']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        /** @var Connection $conn */
        $conn = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection();
        // check social activity
        $count = (int)$conn->fetchColumn('SELECT COUNT(*) FROM micropetitions_answers WHERE petition_id = ? AND user_id = ?', [$petition->getId(), $user->getId()]);
        $this->assertSame(0, $count);
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
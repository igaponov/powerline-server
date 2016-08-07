<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Entity\Poll\Question\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadEducationalContextData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class PollEducationalContextControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api/v2/polls/{poll}/educational-contexts';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Client
     */
    private $client = null;

    public function setUp()
    {
        $this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->client = NULL;
        $this->em = null;
        parent::tearDown();
    }

    public function testGetEducationalContextsWithWrongCredentials()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $question = $repository->getReference('group_question_3');
        $client = $this->client;
        $uri = str_replace('{poll}', $question->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForGetRequest
     */
    public function testGetEducationalContextsIsOk($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadEducationalContextData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{poll}', $question->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
    }

    /**
     * @param $user
     * @param $params
     * @param $errors
     * @dataProvider getInvalidParams
     */
    public function testCreateEducationalContextReturnsErrors($user, $params, $errors)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadEducationalContextData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('group_question_1');
        $client = $this->client;
        $uri = str_replace('{poll}', $question->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        $children = $data['errors']['children'];
        $this->assertEquals(
            ['This poll should contain 3 educational contexts or less.'],
            $data['errors']['errors']
        );
        foreach ($errors as $child => $error) {
            $this->assertEquals([$error], $children[$child]['errors']);
        }
    }

    public function getInvalidParams()
    {
        return [
            'owner' => [
                'user1',
                ['type' => '', 'content' => ''],
                [
                    'type' => 'This value should not be blank.',
                    'content' => 'This value should not be blank.',
                ]
            ],
            'manager' => [
                'user2',
                [
                    'type' => EducationalContext::IMAGE_TYPE,
                    'content' => base64_encode(file_get_contents(__FILE__)),
                ],
                [
                    'content' => 'This file is not a valid image.',
                ]
            ],
        ];
    }

    /**
     * @param $params
     * @dataProvider getValidParams
     */
    public function testCreateEducationalContextIsOk($params)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
        $question = $repository->getReference('group_question_3');
        $client = $this->client;
        $uri = str_replace('{poll}', $question->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"'], json_encode($params));
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($params['type'], $data['type']);
        if ($params['type'] != EducationalContext::IMAGE_TYPE) {
            $this->assertEquals($params['content'], $data['text']);
        } else {
            $this->assertNotEmpty($data['text']);
            $this->assertNotEmpty($data['imageSrc']);
            $this->assertCount(1, $client->getContainer()->get('civix_core.storage.array')->getFiles('educational_image_fs'));
        }
    }

    public function getValidParams()
    {
        return [
            'text' => [
                [
                    'type' => EducationalContext::TEXT_TYPE,
                    'content' => 'Lorem ipsum',
                ],
            ],
            'video' => [
                [
                    'type' => EducationalContext::VIDEO_TYPE,
                    'content' => 'Lorem ipsum',
                ],
            ],
            'image' => [
                [
                    'type' => EducationalContext::IMAGE_TYPE,
                    'content' => base64_encode(file_get_contents(__DIR__.'/../../data/image.png')),
                ],
            ],
        ];
    }

    public function getValidPollCredentialsForGetRequest()
    {
        return [
            'owner' => ['user1', 'group_question_1'],
            'manager' => ['user2', 'group_question_1'],
            'member' => ['user4', 'group_question_1'],
        ];
    }
}
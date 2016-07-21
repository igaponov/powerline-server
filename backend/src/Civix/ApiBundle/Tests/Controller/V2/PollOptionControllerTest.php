<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Faker\Factory;
use Symfony\Component\BrowserKit\Client;

class PollOptionControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/poll-options';
	
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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testUpdateOptionReturnsErrors($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		/** @var Question $question */
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode(['value' => '']));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		$this->assertEquals(['This value should not be blank.'], $children['value']['errors']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testUpdateCommentWithWrongCredentialsReturnsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		/** @var Question $question */
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testUpdateOptionIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$faker = Factory::create();
		$params = [
			'value' => $faker->word,
		];
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($question->getId(), $data['id']);
		$this->assertSame($params['value'], $data['value']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidPollCredentialsForUpdateRequest
     */
	public function testDeleteOptionWithWrongCredentialsReturnsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$client = $this->client;
		$question = $repository->getReference($reference);
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidPollCredentialsForUpdateRequest
     */
	public function testDeleteOptionIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadGroupQuestionData::class,
        ])->getReferenceRepository();
		$question = $repository->getReference($reference);
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$question->getOptions()->get(0)->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(204, $response->getStatusCode(), $response->getContent());
	}

    public function getInvalidPollCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_question_1'],
            'outlier' => ['user1', 'group_question_2'],
        ];
    }

    public function getValidPollCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user1', 'group_question_1'],
            'manager' => ['user2', 'group_question_1'],
        ];
    }
}

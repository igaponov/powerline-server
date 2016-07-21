<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupFollowerTestData;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Client;

class AnnouncementControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/announcements';
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var Client
	 */
	private $client = null;

	/**
	 * @var ProxyReferenceRepository
	 */
	private $repository;

	public function setUp()
	{
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);

		$this->repository = $this->loadFixtures([
			LoadGroupFollowerTestData::class,
		])->getReferenceRepository();

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		$this->client = NULL;
        $this->repository = null;
        parent::tearDown();
	}

	public function testCreateAnnouncementWithWrongCredentials()
	{
		$client = $this->client;
        $group = $this->repository->getReference('group');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token'=>'userfollowtest1']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreateAnnouncementReturnsErrors($params, $errors)
	{
		$client = $this->client;
        $group = $this->repository->getReference('testfollowsecretgroups');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token'=>'userfollowtest1'], json_encode($params));
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

	public function testCreateAnnouncementIsOk()
	{
        $params = [
			'content' => 'some text',
		];
        $client = $this->client;
        $group = $this->repository->getReference('testfollowsecretgroups');
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Token'=>'userfollowtest1'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['id']);
		$this->assertSame($params['content'], $data['content_parsed']);
	}
}

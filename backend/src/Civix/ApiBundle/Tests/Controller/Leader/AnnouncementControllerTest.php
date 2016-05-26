<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Model\Subscription\PackageLimitState;
use Civix\CoreBundle\Service\Subscription\PackageHandler;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class AnnouncementControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api-leader/announcements';
	
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
			LoadGroupData::class,
			LoadGroupAnnouncementData::class,
		])->getReferenceRepository();

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetAnnouncementWithWrongCredentialsReturnsException()
	{
		/** @var Announcement $announcement */
		$announcement = $this->repository->getReference('announcement_group_1');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	/**
	 * @param $reference
	 * @param $published
	 * @dataProvider getAnnouncements
	 */
	public function testGetAnnouncementIsOk($reference, $published)
	{
		$announcement = $this->repository->getReference($reference);
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($announcement->getId(), $data['id']);
		$this->assertSame($published, !empty($data['published_at']));
	}

	public function getAnnouncements()
	{
		return [
			'unpublished' => ['announcement_group_1', false],
			'published' => ['announcement_group_2', true],
		];
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testCreateAnnouncementReturnsErrors($params, $errors)
	{
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
	}

	public function testCreateAnnouncementIsOk()
	{
		$params = [
			'content' => 'some text',
		];
		$client = $this->client;
		$client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertNotNull($data['id']);
		$this->assertSame($params['content'], $data['content_parsed']);
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testUpdateAnnouncementReturnsErrors($params, $errors)
	{
		$client = $this->client;
		$announcement = $this->repository->getReference('announcement_group_1');
		$client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			$this->assertEquals([$error], $children[$child]['errors']);
		}
	}

	public function testUpdateAnnouncementWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$announcement = $this->repository->getReference('announcement_group_1');
		$client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdatePublishedAnnouncementReturnsException()
	{
		$client = $this->client;
		$announcement = $this->repository->getReference('announcement_group_2');
		$client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateAnnouncementIsOk()
	{
		$faker = Factory::create();
		$params = [
			'content' => $faker->text,
		];
		$announcement = $this->repository->getReference('announcement_group_1');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($announcement->getId(), $data['id']);
		$this->assertSame($params['content'], $data['content_parsed']);
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

	public function testPublishAnnouncementWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$announcement = $this->repository->getReference('announcement_group_1');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testPublishAnnouncementWithExceededLimitReturnsException()
	{
		$client = $this->client;
		$client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock(2));
		$announcement = $this->repository->getReference('announcement_group_1');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testPublishPublishedAnnouncementReturnsException()
	{
		$client = $this->client;
		$client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock());
		$announcement = $this->repository->getReference('announcement_group_2');
		$client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"'], '{}');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testPublishAnnouncementIsOk()
	{		
		$announcement = $this->repository->getReference('announcement_group_1');
		$client = $this->client;
		$client->getContainer()->set('civix_core.package_handler', $this->getPackageHandlerMock());
		$client->request('PATCH', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($announcement->getId(), $data['id']);
		$this->assertNotNull($data['published_at']);
	}

	public function testDeleteAnnouncementWithWrongCredentialsReturnsException()
	{
		$client = $this->client;
		$announcement = $this->repository->getReference('announcement_group_1');
		$client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="testfollowsecretgroups"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testDeletePublishedAnnouncementReturnsException()
	{
		$client = $this->client;
		$announcement = $this->repository->getReference('announcement_group_2');
		$client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="group" token="secret_token"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testDeleteAnnouncementIsOk()
	{
		$announcement = $this->repository->getReference('announcement_group_1');
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT.'/'.$announcement->getId(), [], [], ['HTTP_Token'=>'secret_token']);
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
			->will($this->returnValue($packageLimitState));

		return $service;
	}
}

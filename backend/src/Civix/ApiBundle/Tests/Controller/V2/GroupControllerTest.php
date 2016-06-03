<?php
namespace Civix\ApiBundle\Tests\Controller\Leader;

use Civix\CoreBundle\Entity\Group;
use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Client;

class GroupControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups';
	
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
			LoadUserData::class,
			LoadGroupData::class,
			LoadUserGroupData::class,
		])->getReferenceRepository();

		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetGroupsIsOk()
	{
		$data = $this->getGroups('followertest', []);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
	}

	public function testGetGroupsExcludeOwnedIsOk()
	{
		$data = $this->getGroups('userfollowtest1', ['exclude_owned' => true]);
		$this->assertSame(1, $data['totalItems']);
		$this->assertCount(1, $data['payload']);
		$this->assertSame(LoadGroupData::GROUP_NAME, $data['payload'][0]['username']);
	}

	public function testGetGroupsSortedByCreatedAtIsOk()
	{
		$data = $this->getGroups('userfollowtest1', ['sort' => 'created_at', 'sort_dir' => 'DESC']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
		$current = reset($data['payload']);
		while ($next = next($data['payload'])) {
			$this->assertLessThanOrEqual(
				new \DateTime($current['created_at']),
				new \DateTime($next['created_at'])
			);
			$current = $next;
		}
	}

	public function testGetGroupsSortedByPopularityIsOk()
	{
		$data = $this->getGroups('userfollowtest1', ['sort' => 'popularity', 'sort_dir' => 'DESC']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
		$this->assertSame('testfollowprivategroups', $data['payload'][0]['username']);
	}

	public function testGetGroupsExcludeOwnedAndSortedByCreatedAtIsOk()
	{
		$data = $this->getGroups('testuserbookmark1', [
			'exclude_owned' => true,
			'sort' => 'created_at',
			'sort_dir' => 'DESC',
		]);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
		$current = reset($data['payload']);
		while ($next = next($data['payload'])) {
			$this->assertLessThanOrEqual(
				new \DateTime($current['created_at']),
				new \DateTime($next['created_at'])
			);
			$current = $next;
		}
	}

	public function testGetGroupsExcludeOwnedAndSortedByPopularityIsOk()
	{
		$data = $this->getGroups('testuserbookmark1', [
			'exclude_owned' => true,
			'sort' => 'popularity',
			'sort_dir' => 'DESC',
		]);
		$this->assertSame(2, $data['totalItems']);
		$this->assertCount(2, $data['payload']);
	}

	public function testGetGroupNotAuthorized()
	{
		$group = $this->repository->getReference('group');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId());
		$response = $client->getResponse();
		$this->assertEquals(401, $response->getStatusCode(), $response->getContent());
	}

	public function testGetGroupIsOk()
	{
		$group = $this->repository->getReference('group');
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="followertest"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($group->getId(), $data['id']);
	}

	public function testUpdateGroupWithErrors()
	{
		$group = $this->repository->getReference('testfollowsecretgroups');
		$errors = [
			'username' => ['This value should not be blank.'],
			'official_name' => ['This value should not be blank.'],
			'official_type' => ['This value should not be blank.'],
		];
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'], json_encode([
			'username' => '',
			'official_name' => '',
			'official_type' => '',
		]));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$count = 0;
		foreach ($data['errors']['children'] as $property => $arr) {
			if (!empty($arr['errors'])) {
				$count++;
				$this->assertSame($errors[$property], $arr['errors']);
			}
		}
		$this->assertCount($count, $errors);
	}

	public function testUpdateGroupWithWrongPermissions()
	{
		$group = $this->repository->getReference('group');
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'], '');
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateGroupIsOk()
	{
		$group = $this->repository->getReference('testfollowsecretgroups');
		$faker = Factory::create();
		$params = [
			'username' => $faker->userName,
			'manager_first_name' => $faker->firstName,
			'manager_last_name' => $faker->lastName,
			'manager_email' => $faker->email,
			'manager_phone' => $faker->phoneNumber,
			'official_type' => $faker->randomElement(Group::getOfficialTypes()),
			'official_name' => $faker->company,
			'official_description' => $faker->text,
			'acronym' => $faker->company,
			'official_address' => $faker->address,
			'official_city' => $faker->city,
			'official_state' => strtoupper($faker->randomLetter.$faker->randomLetter),
		];
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/'.$group->getId(), [], [], ['HTTP_Authorization'=>'Bearer type="user" token="userfollowtest1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($data as $property => $value) {
			$this->assertSame($value, $data[$property]);
		}
	}

	public function testGetGroupUsersIsEmpty()
	{
		$group = $this->repository->getReference('group');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="userfollowtest1"'];
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(0, $data['totalItems']);
		$this->assertCount(0, $data['payload']);
	}

	public function testGetGroupUsersIsOk()
	{
		$group = $this->repository->getReference('testfollowsecretgroups');
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="userfollowtest1"'];
		$client->request('GET', self::API_ENDPOINT.'/'.$group->getId().'/users', [], [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		$this->assertSame(3, $data['totalItems']);
		$this->assertCount(3, $data['payload']);
		foreach ($data['payload'] as $item) {
			$this->assertThat(
				$item['username'],
				$this->logicalOr('userfollowtest1', 'userfollowtest2', 'userfollowtest3')
			);
		}
	}

	protected function getGroups($username, $params)
	{
		$client = $this->client;
		$headers = ['HTTP_Authorization' => 'Bearer type="user" token="'.$username.'"'];
		$client->request('GET', self::API_ENDPOINT, $params, [], $headers);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['page']);
		$this->assertSame(20, $data['items']);
		return $data;
	}
}

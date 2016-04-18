<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Repository\UserGroupRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class MembershipControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/group/membership';

	/**
	 * @var null|Client
	 */
	private $client = null;

	public function setUp()
	{
		$this->loadFixtures([
			LoadGroupData::class,
		]);
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetMicropetitionConfigIsOk()
	{
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('passcode', $data['membership_control']);
	}

	/**
	 * @param array $values
	 * @param array $errors
	 * @dataProvider getInvalidValues
	 */
	public function testUpdateMembershipControlReturnsErrors($values, $errors)
	{
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($values));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			if ($error) {
				$this->assertContains($error, $children[$child]['errors']);
			} else {
				$this->assertEmpty($children[$child]);
			}
		}
	}

	public function getInvalidValues()
	{
		return [
			'empty data' => [
				[],
				[
					'membership_control' => 'This value should not be blank.',
					'membership_passcode' => null,
				]
			],
			'empty passcode' => [
				[
					'membership_control' => 'passcode',
				],
				[
					'membership_control' => null,
					'membership_passcode' => 'This value should not be blank.',
				]
			],
		];
	}

	/**
	 * @param array $params
	 * @dataProvider getValidValues
	 */
	public function testUpdateMembershipControlIsOk($params)
	{
		$client = $this->client;
		$repo = $this->getMockBuilder(UserGroupRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$client->getContainer()->set('civix_core.repository.user_group_repository', $repo);
		$isPublic = $params['membership_control'] == 'public';
		$repo->expects($isPublic ? $this->once() : $this->never())
			->method('setApprovedAllUsersInGroup');
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['membership_control'], $data['membership_control']);
	}

	public function getValidValues()
	{
		return [
			'public' => [
				[
					'membership_control' => 'public',
				]
			],
			'approval' => [
				[
					'membership_control' => 'approval',
				],
			],
			'passcode' => [
				[
					'membership_control' => 'passcode',
					'membership_passcode' => 'XXX',
				],
			],
		];
	}
}
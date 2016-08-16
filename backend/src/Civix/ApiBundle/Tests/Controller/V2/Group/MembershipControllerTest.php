<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Entity\Subscription\Subscription;
use Civix\CoreBundle\Repository\UserGroupRepository;
use Civix\CoreBundle\Service\Subscription\SubscriptionManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class MembershipControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/membership';

	/**
	 * @var null|Client
	 */
	private $client = null;

	public function setUp()
	{
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		$this->client = NULL;
        parent::tearDown();
    }

	public function testGetMicropetitionConfigWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForGetRequest
     */
	public function testGetMicropetitionConfigIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('passcode', $data['membership_control']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForUpdateRequest
     */
    public function testUpdateMembershipControlWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $service = $this->getSubscriptionManagerMock(Subscription::PACKAGE_TYPE_SILVER);
        $client->getContainer()->set("civix_core.subscription_manager", $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateMembershipControlWithFreeSubscriptionThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $service = $this->getSubscriptionManagerMock(Subscription::PACKAGE_TYPE_FREE);
        $client->getContainer()->set("civix_core.subscription_manager", $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

	/**
	 * @param array $values
	 * @param array $errors
	 * @dataProvider getInvalidValues
	 */
	public function testUpdateMembershipControlReturnsErrors($values, $errors)
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $service = $this->getSubscriptionManagerMock(Subscription::PACKAGE_TYPE_SILVER);
        $client->getContainer()->set("civix_core.subscription_manager", $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"'], json_encode($values));
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
     * @param $user
     * @param array $params
     * @dataProvider getValidValues
     */
	public function testUpdateMembershipControlIsOk($user, $params)
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
		$repo = $this->getMockBuilder(UserGroupRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$client->getContainer()->set('civix_core.repository.user_group_repository', $repo);
		$isPublic = $params['membership_control'] == 'public';
		$repo->expects($isPublic ? $this->once() : $this->never())
			->method('setApprovedAllUsersInGroup');
        $service = $this->getSubscriptionManagerMock(Subscription::PACKAGE_TYPE_SILVER);
        $client->getContainer()->set("civix_core.subscription_manager", $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
		$client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($params['membership_control'], $data['membership_control']);
	}

	public function getValidValues()
	{
		return [
			'public, owner' => [
			    'user3',
				[
					'membership_control' => 'public',
				]
			],
			'approval, manager' => [
			    'user2',
				[
					'membership_control' => 'approval',
				],
			],
			'passcode, manager' => [
			    'user2',
				[
					'membership_control' => 'passcode',
					'membership_passcode' => 'XXX',
				],
			],
		];
	}

    public function getInvalidGroupCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user1', 'group_3'],
        ];
    }

    public function getValidGroupCredentialsForGetRequest()
    {
        return [
            'owner' => ['user3', 'group_3'],
            'manager' => ['user2', 'group_3'],
            'member' => ['user4', 'group_3'],
        ];
    }

    private function getSubscriptionManagerMock($packageType)
    {
        $service = $this->getMockBuilder(SubscriptionManager::class)
            ->setMethods(['getSubscription'])
            ->disableOriginalConstructor()
            ->getMock();
        $subscription = new Subscription();
        $subscription->setPackageType($packageType);
        $stub = $this->returnValue($subscription);
        $service->expects($this->once())
            ->method('getSubscription')
            ->will($stub);

        return $service;
    }
}
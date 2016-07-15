<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Model\Subscription\PackageLimitState;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class MicropetitionConfigControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/micro-petitions-config';

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
		$params = [
			'petition_percent' => 55,
			'petition_duration' => 35,
			'petition_per_month' => 10,
		];
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($params as $param => $value) {
			$this->assertSame($value, $data[$param]);
		}
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForUpdateRequest
     */
    public function testUpdateMicropetitionConfigWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $service = $this->getPackageHandlerMock();
        $client->getContainer()->set('civix_core.package_handler', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode([]));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testUpdateMicropetitionConfigReachedPackageLimitThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_3');
        $client = $this->client;
        $service = $this->getPackageHandlerMock(1, 1);
        $client->getContainer()->set('civix_core.package_handler', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"'], json_encode([]));
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

	/**
	 * @param string $value
	 * @param array $errors
	 * @dataProvider getConfigValues
	 */
	public function testUpdateMicropetitionConfigReturnsErrors($value, $errors)
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$client = $this->client;
        $service = $this->getPackageHandlerMock();
        $client->getContainer()->set('civix_core.package_handler', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(
			[
				'petition_percent' => $value,
				'petition_duration' => $value,
				'petition_per_month' => $value,
			]
		));
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

	public function getConfigValues()
	{
		return [
			'invalid_type' => ['x', [
				'petition_percent' => 'This value is not valid.',
				'petition_duration' => 'This value is not valid.',
				'petition_per_month' => 'This value is not valid.',
			]],
			'range' => [100, [
				'petition_percent' => 'This value should be 50 or less.',
				'petition_duration' => 'This value should be 30 or less.',
				'petition_per_month' => null,
			]],
		];
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForUpdateRequest
     */
	public function testUpdateMicropetitionConfigIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
		$client = $this->client;
		$params = [
			'petition_percent' => 15,
			'petition_duration' => 25,
			'petition_per_month' => 35,
		];
        $service = $this->getPackageHandlerMock();
        $client->getContainer()->set('civix_core.package_handler', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($params as $param => $value) {
			$this->assertSame($value, $data[$param]);
		}
	}

    public function getInvalidGroupCredentialsForUpdateRequest()
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user1', 'group_3'],
        ];
    }

    public function getValidGroupCredentialsForUpdateRequest()
    {
        return [
            'owner' => ['user3', 'group_3'],
            'manager' => ['user2', 'group_3'],
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

    /**
     * @param int $current
     * @param int $limit
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getPackageHandlerMock($current = 1, $limit = 2)
    {
        $packageState = new PackageLimitState();
        $packageState->setCurrentValue($current);
        $packageState->setLimitValue($limit);
        $service = $this->getServiceMockBuilder('civix_core.package_handler')
            ->setMethods(['getPackageStateForMicropetition'])
            ->getMock();
        $service->expects($this->once())
            ->method('getPackageStateForMicropetition')
            ->will($this->returnValue($packageState));

        return $service;
    }
}
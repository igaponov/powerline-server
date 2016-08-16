<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\BalancedBundle\Service\BalancedPaymentManager;
use Civix\CoreBundle\Entity\Customer\Customer;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadCustomerGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Faker\Factory;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class PaymentAccountSettingsControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/payment-settings';

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

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForGetRequest
     */
	public function testGetPaymentSettingsWithWrongCredentialsThrowsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getValidGroupCredentialsForGetRequest
     */
	public function testGetPaymentSettingsIsOk($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(Customer::ACCOUNT_TYPE_BUSINESS, $data['account_type']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForUpdateRequest
     */
    public function testUpdatePaymentSettingsWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testUpdatePaymentSettingsReturnsErrors($params, $errors)
	{
        $repository = $this->loadFixtures([
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$balancedPaymentManager = $this->getMockBuilder(BalancedPaymentManager::class)
			->disableOriginalConstructor()
			->getMock();
		$balancedPaymentManager->expects($this->never())->method('updateCustomer');
		$client = $this->client;
		$client->getContainer()->set('civix_balanced.payment_manager', $balancedPaymentManager);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$children = $data['errors']['children'];
		foreach ($errors as $child => $error) {
			if ($error) {
				$this->assertEquals([$error], $children[$child]['errors']);
			} else {
				$this->assertEmpty($children[$child]);
			}
		}
	}

	public function getInvalidParams()
	{
		return [
			'empty type' => [
				[], 
				[
					'account_type' => 'This value should not be blank.',
				]
			],
			'wrong type' => [
				[
					'account_type' => 'wrong type',
				],
				[
					'account_type' => 'The value you selected is not a valid choice.',
				]
			],
			'empty business values' => [
				[
					'account_type' => Customer::ACCOUNT_TYPE_BUSINESS,
				],
				[
					'business_name' => 'This value should not be blank.',
					'ein' => 'This value should not be blank.',
				]
			],
			'empty personal values' => [
				[
					'account_type' => Customer::ACCOUNT_TYPE_PERSONAL,
				],
				[
					'name' => 'This value should not be blank.',
					'birth' => 'This value should not be blank.',
					'ssn_last_4' => 'This value should not be blank.',
				]
			],
		];
	}

    /**
     * @param $user
     * @param $params
     * @dataProvider getValidParams
     */
	public function testUpdatePaymentSettingsIsOk($user, $params)
	{
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$balancedPaymentManager = $this->getMockBuilder(BalancedPaymentManager::class)
			->disableOriginalConstructor()
			->getMock();
		$balancedPaymentManager->expects($this->once())->method('updateCustomer');
		$client = $this->client;
		$client->getContainer()->set('civix_balanced.payment_manager', $balancedPaymentManager);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(['account_type' => $params['account_type']], $data);
	}

	public function getValidParams()
	{
		$faker = Factory::create();
		return [
			'business, manager' => [
			    'user2',
				[
					'account_type' => Customer::ACCOUNT_TYPE_BUSINESS,
					'business_name' => $faker->company,
					'ein' => $faker->ean8,
				]
			],
			'personal, owner' => [
			    'user1',
				[
					'account_type' => Customer::ACCOUNT_TYPE_PERSONAL,
					'name' => $faker->name,
					'birth' => $faker->date(),
					'ssn_last_4' => substr($faker->ean8, 4),
				]
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
            'owner' => ['user1', 'group_1'],
            'manager' => ['user2', 'group_1'],
        ];
    }

    public function getInvalidGroupCredentialsForGetRequest()
    {
        return [
            'member' => ['user4', 'group_3'],
            'outlier' => ['user1', 'group_3'],
        ];
    }
}
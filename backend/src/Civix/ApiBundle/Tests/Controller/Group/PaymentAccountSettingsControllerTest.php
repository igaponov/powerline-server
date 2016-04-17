<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\BalancedBundle\Service\BalancedPaymentManager;
use Civix\CoreBundle\Entity\Customer\Customer;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadCustomerGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadStripeCustomerGroupData;
use Faker\Factory;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class PaymentAccountSettingsControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/group/payment-settings';

	/**
	 * @var null|Client
	 */
	private $client = null;

	public function setUp()
	{
		$this->loadFixtures([
			LoadGroupData::class,
			LoadStripeCustomerGroupData::class,
			LoadCustomerGroupData::class,
		]);
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	/**
	 * @param array $params
	 * @param array $errors
	 * @dataProvider getInvalidParams
	 */
	public function testUpdatePaymentSettingsReturnsErrors($params, $errors)
	{
		$balancedPaymentManager = $this->getMockBuilder(BalancedPaymentManager::class)
			->disableOriginalConstructor()
			->getMock();
		$balancedPaymentManager->expects($this->never())->method('updateCustomer');
		$client = $this->client;
		$client->getContainer()->set('civix_balanced.payment_manager', $balancedPaymentManager);
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
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
	 * @param $params
	 * @dataProvider getValidParams
	 */
	public function testUpdatePaymentSettingsIsOk($params)
	{
		$balancedPaymentManager = $this->getMockBuilder(BalancedPaymentManager::class)
			->disableOriginalConstructor()
			->getMock();
		$balancedPaymentManager->expects($this->once())->method('updateCustomer');
		$client = $this->client;
		$client->getContainer()->set('civix_balanced.payment_manager', $balancedPaymentManager);
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(['account_type' => $params['account_type']], $data);
	}

	public function getValidParams()
	{
		$faker = Factory::create();
		return [
			'business' => [
				[
					'account_type' => Customer::ACCOUNT_TYPE_BUSINESS,
					'business_name' => $faker->company,
					'ein' => $faker->ean8,
				]
			],
			'personal' => [
				[
					'account_type' => Customer::ACCOUNT_TYPE_PERSONAL,
					'name' => $faker->name,
					'birth' => $faker->date(),
					'ssn_last_4' => substr($faker->ean8, 4),
				]
			],
		];
	}
}
<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadStripeCustomerGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSubscriptionData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class MicropetitionControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/group/micro-petitions';

	/**
	 * @var null|Client
	 */
	private $client = null;
	/**
	 * @var AbstractExecutor
	 */
	private $executor;

	public function setUp()
	{
		$this->executor = $this->loadFixtures([
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
		$params = [
			'petition_percent' => 45,
			'petition_duration' => 25,
			'petition_per_month' => 4,
		];
		$client->request('GET', self::API_ENDPOINT.'/config', [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($params as $param => $value) {
			$this->assertSame($value, $data[$param]);
		}
	}

	/**
	 * @param string $value
	 * @param array $errors
	 * @dataProvider getConfigValues
	 */
	public function testUpdateMicropetitionConfigReturnsErrors($value, $errors)
	{
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/config', [], [], ['HTTP_Token'=>'secret_token'], json_encode(
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

	public function testUpdateMicropetitionConfigAccessDenied()
	{
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT.'/config', [], [], ['HTTP_Token'=>'testfollowsecretgroups'], json_encode([]));
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testUpdateMicropetitionConfigIsOk()
	{
		$client = $this->client;
		$params = [
			'petition_percent' => 15,
			'petition_duration' => 25,
			'petition_per_month' => 35,
		];
		$client->request('PUT', self::API_ENDPOINT.'/config', [], [], ['HTTP_Token'=>'secret_token'], json_encode($params));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		foreach ($params as $param => $value) {
			$this->assertSame($value, $data[$param]);
		}
	}
}
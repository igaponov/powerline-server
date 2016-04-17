<?php
namespace Civix\ApiBundle\Tests\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadStripeCustomerGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSubscriptionData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class SubscriptionControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/group/subscription';

	/**
	 * @var null|Client
	 */
	private $client = null;

	/**
	 * @var Stripe|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $stripe;
	
	public function setUp()
	{
		$this->loadFixtures([
			LoadGroupData::class,
			LoadStripeCustomerGroupData::class,
			LoadSubscriptionData::class,
		]);
		// Creates a initial client
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$this->stripe = $this->getMockBuilder(Stripe::class)
			->setMethods(['handleSubscription', 'cancelSubscription'])
			->disableOriginalConstructor()
			->getMock();
		$this->client->getContainer()->set('civix_core.stripe', $this->stripe);
	}

	public function tearDown()
	{
		// Creates a initial client
		$this->client = NULL;
	}

	public function testGetSubscriptionIsOk()
	{
		$client = $this->client;
		$client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('platinum', $data['package_type']);
	}
	
	public function testUpdateSubscriptionReturnsErrors()
	{
		$client = $this->client;
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$errors = $data['errors']['children'];
		$this->assertContains('This value should not be blank.', $errors['package_type']['errors']);
	}

	public function testUpdateSubscriptionIsOk()
	{
		$this->stripe->expects($this->once())
			->method('handleSubscription')
			->will($this->returnArgument(0));
		$client = $this->client;
		$type = 'silver';
		$client->request('PUT', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token'], json_encode(['package_type' => $type, 'coupon' => 'XXX']));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($type, $data['package_type']);
	}

	public function testDeleteSubscriptionIsOk()
	{
		$this->stripe->expects($this->once())
			->method('cancelSubscription')
			->will($this->returnArgument(0));
		$client = $this->client;
		$client->request('DELETE', self::API_ENDPOINT, [], [], ['HTTP_Token'=>'secret_token']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['id']);
	}
}
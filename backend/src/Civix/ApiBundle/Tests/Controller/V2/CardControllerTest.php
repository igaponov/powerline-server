<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Stripe\Card;
use Civix\CoreBundle\Entity\Stripe\CustomerInterface;
use Civix\CoreBundle\Entity\Stripe\CustomerUser;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadCustomerUserData;
use Symfony\Bundle\FrameworkBundle\Client;

class CardControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/cards';

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

    public function testGetCardsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadCustomerUserData::class,
        ])->getReferenceRepository();
        /** @var CustomerUser $customer */
        $customer = $repository->getReference('stripe_customer_user_1');
        $client = $this->client;
        $client->request('GET', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($customer->getCards(), $data);
    }

    public function testCreateCardWithWrongDataReturnsError()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $client = $this->client;
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, ['source' => 'This value should not be blank.']);
    }

	public function testCreateCardIsOk()
	{
	    $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
	    $response = (object)[
	        'id' => 'id0',
        ];
	    $service->expects($this->once())
            ->method('createCustomer')
            ->with($this->isInstanceOf(User::class))
            ->willReturn($response);
	    $service->expects($this->once())
            ->method('addCard')
            ->with(
                $this->isInstanceOf(CustomerInterface::class),
                $this->isInstanceOf(Card::class)
            );
	    $card = [
            'id' => 'acc0',
            'last4' => 'last4',
            'brand' => 'US Bank Name',
            'funding' => 'yyy',
        ];
	    $service->expects($this->once())
            ->method('getCards')
            ->with($this->isInstanceOf(CustomerInterface::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$card],
                ]
            );
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['source' => '#123']));
		$response = $client->getResponse();
		$this->assertEquals(201, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertEquals($card, $data['cards'][0]);
	}

	public function testCreateCardWithExistentAccountIsOk()
	{
	    $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
	    $service->expects($this->never())
            ->method('createCustomer');
	    $service->expects($this->once())
            ->method('addCard')
            ->with(
                $this->isInstanceOf(CustomerInterface::class),
                $this->isInstanceOf(Card::class)
            );
	    $card = [
            'id' => 'acc1',
            'last4' => '7890',
            'brand' => 'US Bank Name',
            'funding' => 'yyy',
        ];
	    $service->expects($this->once())
            ->method('getCards')
            ->with($this->isInstanceOf(CustomerInterface::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$card],
                ]
            );
        $this->loadFixtures([
            LoadCustomerUserData::class,
        ]);
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $client->request('POST', self::API_ENDPOINT, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['source' => '#123']));
		$response = $client->getResponse();
		$this->assertEquals(201, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertEquals($card, $data['cards'][0]);
	}

    public function testDeleteCardIsOk()
    {
        $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('removeCard')
            ->with(
                $this->isInstanceOf(CustomerInterface::class),
                $this->callback(function (Card $card) {
                    $this->assertEquals('11233', $card->getId());

                    return true;
                })
            );
        $card = [
            'id' => 'acc1',
            'last4' => '7890',
            'brand' => 'US Bank Name',
            'funding' => 'yyy',
        ];
        $service->expects($this->once())
            ->method('getCards')
            ->with($this->isInstanceOf(CustomerInterface::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$card],
                ]
            );
        $this->loadFixtures([
            LoadCustomerUserData::class,
        ]);
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $client->request('DELETE', self::API_ENDPOINT.'/11233', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }
}
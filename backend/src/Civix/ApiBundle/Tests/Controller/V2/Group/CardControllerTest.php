<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Card;
use Civix\CoreBundle\Entity\Stripe\CustomerGroup;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadCustomerGroupData;
use Symfony\Bundle\FrameworkBundle\Client;

class CardControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/cards';

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
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        /** @var CustomerGroup $customer */
        $customer = $repository->getReference('stripe_customer_group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $customer->getUser()->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($customer->getCards(), $data);
    }

    public function testCreateCardWithWrongDataReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, ['source' => 'This value should not be blank.']);
    }

	public function testCreateCardIsOk()
	{
	    $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
	    $response = (object)[
	        'id' => 'id1',
        ];
	    $service->expects($this->once())
            ->method('createCustomer')
            ->with($this->isInstanceOf(Group::class))
            ->willReturn($response);
	    $service->expects($this->once())
            ->method('addCard')
            ->with(
                $this->isInstanceOf(CustomerGroup::class),
                $this->isInstanceOf(Card::class)
            );
	    $card = [
            'id' => 'acc1',
            'last4' => 'last4',
            'brand' => 'US Bank Name',
            'funding' => 'yyy',
        ];
	    $service->expects($this->once())
            ->method('getCards')
            ->with($this->isInstanceOf(CustomerGroup::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$card],
                ]
            );
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['source' => '#123']));
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
                $this->isInstanceOf(CustomerGroup::class),
                $this->isInstanceOf(Card::class)
            );
	    $card = [
            'id' => 'acc2',
            'last4' => '7891',
            'brand' => 'US Bank Name',
            'funding' => 'yyyy',
        ];
	    $service->expects($this->once())
            ->method('getCards')
            ->with($this->isInstanceOf(CustomerGroup::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$card],
                ]
            );
        $repository = $this->loadFixtures([
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['source' => '#123']));
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
                $this->isInstanceOf(CustomerGroup::class),
                $this->callback(function (Card $card) {
                    $this->assertEquals('22455', $card->getId());

                    return true;
                })
            );
        $card = [
            'id' => 'acc2',
            'last4' => '7891',
            'brand' => 'US Bank Name',
            'funding' => 'yyyy',
        ];
        $service->expects($this->once())
            ->method('getCards')
            ->with($this->isInstanceOf(CustomerGroup::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$card],
                ]
            );
        $repository = $this->loadFixtures([
            LoadCustomerGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('DELETE', $uri.'/22455', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }
}
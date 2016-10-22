<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSubscriptionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class SubscriptionControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/subscription';

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
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
		$this->stripe = $this->getMockBuilder(Stripe::class)
			->setMethods(['handleSubscription', 'cancelSubscription'])
			->disableOriginalConstructor()
			->getMock();
		$this->client->getContainer()->set('civix_core.stripe', $this->stripe);
	}

	public function tearDown()
	{
		$this->client = NULL;
        $this->stripe = null;
        parent::tearDown();
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentials
     */
	public function testGetSubscriptionWithWrongCredentialsThrowsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

	public function testGetSubscriptionIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('platinum', $data['package_type']);
	}

	public function testGetFreeSubscriptionIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('free', $data['package_type']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentials
     */
    public function testUpdateSubscriptionWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

	public function testUpdateSubscriptionReturnsErrors()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame('Validation Failed', $data['message']);
		$errors = $data['errors']['children'];
		$this->assertContains('This value should not be blank.', $errors['package_type']['errors']);
	}

	public function testUpdateSubscriptionIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$this->stripe->expects($this->once())
			->method('handleSubscription')
			->will($this->returnArgument(0));
		$client = $this->client;
		$type = 'silver';
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['package_type' => $type, 'coupon' => 'XXX']));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($type, $data['package_type']);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentials
     */
    public function testDeleteSubscriptionWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

	public function testDeleteSubscriptionIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$this->stripe->expects($this->once())
			->method('cancelSubscription')
			->will($this->returnArgument(0));
		$client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('DELETE', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame(1, $data['id']);
	}

    public function getInvalidGroupCredentials()
    {
        return [
            'manager' => ['user2', 'group_3'],
            'member' => ['user4', 'group_3'],
            'outlier' => ['user1', 'group_3'],
        ];
    }
}
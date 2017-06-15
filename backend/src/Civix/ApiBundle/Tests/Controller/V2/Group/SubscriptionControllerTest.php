<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSubscriptionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserDiscountCodeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Stripe\Error\ApiConnection;
use Stripe\Error\Card;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;

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
			->setMethods(['handleSubscription', 'cancelSubscription', 'createCoupon'])
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
            ->with($this->callback(function (Subscription $subscription) {
                $this->assertEquals('XXX', $subscription->getCoupon()->getCode());

                return true;
            }))
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

	public function testUpdateSubscriptionWithoutCouponIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
		$this->stripe->expects($this->once())
			->method('handleSubscription')
            ->with($this->callback(function (Subscription $subscription) {
                $this->assertNull($subscription->getCoupon()->getCode());

                return true;
            }))
			->will($this->returnArgument(0));
		$client = $this->client;
		$type = 'silver';
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['package_type' => $type, 'coupon' => '']));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($type, $data['package_type']);
	}

	public function testUpdateSubscriptionWithStripeCouponIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $message = 'Invalid coupon code';
        $this->stripe->expects($this->once())
			->method('handleSubscription')
            ->with($this->callback(function (Subscription $subscription) {
                $this->assertEquals('YYY', $subscription->getCoupon()->getCode());

                return true;
            }))
			->willThrowException(new Card($message, null, null, null, '', '{}'));
		$client = $this->client;
		$type = 'silver';
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['package_type' => $type, 'coupon' => 'YYY']));
		$response = $client->getResponse();
		$this->assertEquals(400, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($message, $data['message']);
	}

	public function testUpdateSubscriptionWithValidCouponIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
            LoadUserDiscountCodeData::class,
        ])->getReferenceRepository();
        /** @var DiscountCode $code */
        $code = $repository->getReference('discount_code_3');
        $group = $repository->getReference('group_1');
        $user = $repository->getReference('user_3');
		$this->stripe->expects($this->once())
			->method('handleSubscription')
            ->with($this->callback(function (Subscription $subscription) use ($code) {
                $this->assertEquals($code->getOriginalCode(), $subscription->getCoupon()->getCode());

                return true;
            }))
			->will($this->returnArgument(0));
		$rewardCodeOriginal = uniqid();
        $this->stripe->expects($this->once())
            ->method('createCoupon')
            ->with($this->isInstanceOf(User::class))
            ->willReturn($rewardCodeOriginal);
		$client = $this->client;
		$client->enableProfiler();
		$type = 'silver';
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['package_type' => $type, 'coupon' => $code->getCode()]));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($type, $data['package_type']);
		$conn = $client->getContainer()->get('doctrine.dbal.default_connection');
		$count = $conn->fetchColumn('SELECT COUNT(*) FROM discount_code_uses WHERE discount_code_id = ?', [$code->getId()]);
		$this->assertEquals(3, $count);
        $rewardCode = $conn->fetchColumn('SELECT code FROM discount_codes WHERE original_code = ?', [$rewardCodeOriginal]);
        $this->assertNotEmpty($rewardCode);
        /** @var MessageDataCollector $collector */
        $collector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertSame(1, $collector->getMessageCount());
        /** @var \Swift_Message $message */
        $message = $collector->getMessages()[0];
        $this->assertSame([$user->getEmail() => null], $message->getTo());
        $this->assertSame('You\'ve earned a Powerline Reward!', $message->getSubject());
        $this->assertRegExp("/reward code $rewardCode/", $message->getBody());
	}

	public function testUpdateSubscriptionStripeThrowsExceptionIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
            LoadUserDiscountCodeData::class,
        ])->getReferenceRepository();
        /** @var DiscountCode $code */
        $code = $repository->getReference('discount_code_3');
        $group = $repository->getReference('group_1');
		$this->stripe->expects($this->once())
			->method('handleSubscription')
            ->with($this->callback(function (Subscription $subscription) use ($code) {
                $this->assertEquals($code->getOriginalCode(), $subscription->getCoupon()->getCode());

                return true;
            }))
			->will($this->returnArgument(0));
        $this->stripe->expects($this->once())
            ->method('createCoupon')
            ->willThrowException(new ApiConnection('Connection error'));
		$client = $this->client;
		$client->enableProfiler();
		$type = 'silver';
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['package_type' => $type, 'coupon' => $code->getCode()]));
		$response = $client->getResponse();
		$this->assertEquals(500, $response->getStatusCode(), $response->getContent());
		$conn = $client->getContainer()->get('doctrine.dbal.default_connection');
		$count = $conn->fetchColumn('SELECT COUNT(*) FROM discount_code_uses WHERE discount_code_id = ?', [$code->getId()]);
		$this->assertEquals(2, $count);
	}

	public function testUpdateSubscriptionWithValidCouponWithoutGivingRewardCodeIsOk()
	{
        $repository = $this->loadFixtures([
            LoadSubscriptionData::class,
            LoadUserDiscountCodeData::class,
        ])->getReferenceRepository();
        /** @var DiscountCode $code */
        $code = $repository->getReference('discount_code_1');
        $group = $repository->getReference('group_3');
		$this->stripe->expects($this->once())
			->method('handleSubscription')
            ->with($this->callback(function (Subscription $subscription) use ($code) {
                $this->assertEquals($code->getOriginalCode(), $subscription->getCoupon()->getCode());

                return true;
            }))
			->will($this->returnArgument(0));
		$this->stripe->expects($this->never())
            ->method('createCoupon');
		$client = $this->client;
		$type = 'silver';
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('PUT', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user3"'], json_encode(['package_type' => $type, 'coupon' => $code->getCode()]));
		$response = $client->getResponse();
		$this->assertEquals(200, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertSame($type, $data['package_type']);
		$conn = $client->getContainer()->get('doctrine.dbal.default_connection');
		$count = $conn->fetchColumn('SELECT COUNT(*) FROM discount_code_uses WHERE discount_code_id = ?', [$code->getId()]);
		$this->assertEquals(1, $count);
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
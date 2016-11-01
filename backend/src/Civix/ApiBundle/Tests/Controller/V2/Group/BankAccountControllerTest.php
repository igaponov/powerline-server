<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\AccountGroup;
use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Service\Stripe;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class BankAccountControllerTest extends WebTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{group}/bank-accounts';

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
     * @dataProvider getInvalidGroupCredentialsForRequest
     */
	public function testGetBankAccountsWithWrongCredentialsThrowsException($user, $reference)
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
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testGetBankAccountsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        /** @var AccountGroup $account */
        $account = $repository->getReference('stripe_account_1');
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($account->getBankAccounts(), $data);
    }

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForRequest
     */
	public function testCreateBankAccountWithWrongCredentialsThrowsException($user, $reference)
	{
        $repository = $this->loadFixtures([
            LoadGroupManagerData::class,
            LoadUserGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    public function testCreateBankAccountWithWrongDataReturnsError()
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

	public function testCreateBankAccountIsOk()
	{
	    $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
	    $response = (object)[
	        'id' => 'id0',
            'keys' => (object)[
                'secret' => 'xxx_secret',
                'publishable' => 'xxx_publishable',
            ],
        ];
	    $service->expects($this->once())
            ->method('createAccount')
            ->with($this->isInstanceOf(Group::class))
            ->willReturn($response);
	    $service->expects($this->once())
            ->method('addBankAccount')
            ->with(
                $this->isInstanceOf(AccountInterface::class),
                $this->isInstanceOf(BankAccount::class)
            );
	    $account = [
            'id' => 'acc0',
            'last4' => 'last4',
            'bank_name' => 'Bank Name',
            'country' => 'US',
            'currency' => 'USD',
        ];
	    $service->expects($this->once())
            ->method('getBankAccounts')
            ->with($this->isInstanceOf(AccountInterface::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$account],
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
		$this->assertEquals($account, $data['bank_accounts'][0]);
	}

	public function testCreateBankAccountWithExistentAccountIsOk()
	{
	    $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
	    $service->expects($this->never())
            ->method('createAccount');
	    $service->expects($this->once())
            ->method('addBankAccount')
            ->with(
                $this->isInstanceOf(AccountInterface::class),
                $this->isInstanceOf(BankAccount::class)
            );
	    $account = [
            'id' => 'acc1',
            'last4' => '7890',
            'bank_name' => 'US Bank Name',
            'country' => 'US',
            'currency' => 'usd',
        ];
	    $service->expects($this->once())
            ->method('getBankAccounts')
            ->with($this->isInstanceOf(AccountInterface::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$account],
                ]
            );
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['source' => '#123']));
		$response = $client->getResponse();
		$this->assertEquals(201, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertEquals($account, $data['bank_accounts'][0]);
	}

    /**
     * @param $user
     * @param $reference
     * @dataProvider getInvalidGroupCredentialsForRequest
     */
    public function testDeleteBankAccountWithWrongCredentialsThrowsException($user, $reference)
    {
        $repository = $this->loadFixtures([
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference($reference);
        $client = $this->client;
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('DELETE', $uri.'/22455', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testDeleteBankAccountIsOk()
    {
        $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('removeBankAccount')
            ->with(
                $this->isInstanceOf(AccountGroup::class),
                $this->callback(function (BankAccount $bankAccount) {
                    $this->assertEquals('22455', $bankAccount->getId());

                    return true;
                })
            );
        $account = [
            'id' => 'acc1',
            'last4' => '7890',
            'bank_name' => 'US Bank Name',
            'country' => 'US',
            'currency' => 'usd',
        ];
        $service->expects($this->once())
            ->method('getBankAccounts')
            ->with($this->isInstanceOf(AccountGroup::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$account],
                ]
            );
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_1');
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{group}', $group->getId(), self::API_ENDPOINT);
        $client->request('DELETE', $uri.'/22455', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }

    public function getInvalidGroupCredentialsForRequest()
    {
        return [
            'manager' => ['user2', 'group_1'],
            'member' => ['user4', 'group_1'],
            'outlier' => ['user4', 'group_2'],
        ];
    }
}
<?php
namespace Civix\ApiBundle\Tests\Controller\V2;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Service\Stripe;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class BankAccountControllerTestCase extends WebTestCase
{
	/**
	 * @var null|Client
	 */
	protected $client = null;

    abstract protected function getApiEndpoint();

	public function setUp()
	{
		$this->client = $this->makeClient(false, ['CONTENT_TYPE' => 'application/json']);
	}

	public function tearDown()
	{
		$this->client = NULL;
        parent::tearDown();
    }

	public function getBankAccountsWithWrongCredentialsThrowsException(LeaderContentRootInterface $root, $user)
	{
		$client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function getBankAccountsIsOk(LeaderContentRootInterface $root, AccountInterface $account)
    {
        $client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('GET', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($account->getBankAccounts(), $data);
    }

	public function createBankAccountWithWrongCredentialsThrowsException(LeaderContentRootInterface $root, $user)
	{
        $client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
		$response = $client->getResponse();
		$this->assertEquals(403, $response->getStatusCode(), $response->getContent());
	}

    public function createBankAccountWithWrongDataReturnsError(LeaderContentRootInterface $root)
    {
        $client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertResponseHasErrors($response, ['source' => 'This value should not be blank.']);
    }

	public function createBankAccountIsOk(LeaderContentRootInterface $root, $class)
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
            ->with($this->isInstanceOf($class))
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
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['source' => '#123', 'dob' => date('Y-m-d')]));
		$response = $client->getResponse();
		$this->assertEquals(201, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertEquals($account, $data['bank_accounts'][0]);
	}

	public function createBankAccountWithExistentAccountIsOk(LeaderContentRootInterface $root)
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
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('POST', $uri, [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"'], json_encode(['source' => '#123']));
		$response = $client->getResponse();
		$this->assertEquals(201, $response->getStatusCode(), $response->getContent());
		$data = json_decode($response->getContent(), true);
		$this->assertEquals($account, $data['bank_accounts'][0]);
	}

    public function deleteBankAccountWithWrongCredentialsThrowsException(LeaderContentRootInterface $root, $user)
    {
        $client = $this->client;
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('DELETE', $uri.'/22455', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="'.$user.'"']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function deleteBankAccountIsOk(LeaderContentRootInterface $root)
    {
        $service = $this->getMockBuilder(Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('removeBankAccount')
            ->with(
                $this->isInstanceOf(Account::class),
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
            ->with($this->isInstanceOf(Account::class))
            ->willReturn(
                (object)[
                    'data' => [(object)$account],
                ]
            );
        $client = $this->client;
        $client->getContainer()->set('civix_core.stripe', $service);
        $uri = str_replace('{root}', $root->getId(), $this->getApiEndpoint());
        $client->request('DELETE', $uri.'/22455', [], [], ['HTTP_Authorization'=>'Bearer type="user" token="user1"']);
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
    }
}
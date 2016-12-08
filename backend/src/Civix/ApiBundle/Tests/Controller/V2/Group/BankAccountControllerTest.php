<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Group;

use Civix\ApiBundle\Tests\Controller\V2\BankAccountControllerTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupManagerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupData;

class BankAccountControllerTest extends BankAccountControllerTestCase
{
	const API_ENDPOINT = '/api/v2/groups/{root}/bank-accounts';

    protected function getApiEndpoint()
    {
        return self::API_ENDPOINT;
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
        /** @var Group $group */
        $group = $repository->getReference($reference);
		$this->getBankAccountsWithWrongCredentialsThrowsException($group, $user);
    }

    public function testGetBankAccountsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
            LoadUserGroupData::class,
            LoadGroupManagerData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        /** @var Account $account */
        $account = $repository->getReference('stripe_account_1');
        $this->getBankAccountsIsOk($group, $account);
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
        /** @var Group $group */
        $group = $repository->getReference($reference);
        $this->createBankAccountWithWrongCredentialsThrowsException($group, $user);
	}

    public function testCreateBankAccountWithWrongDataReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $this->createBankAccountWithWrongDataReturnsError($group);
    }

	public function testCreateBankAccountIsOk()
	{
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $this->createBankAccountIsOk($group, Group::class);
	}

	public function testCreateBankAccountWithExistentAccountIsOk()
	{
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $this->createBankAccountWithExistentAccountIsOk($group);
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
        /** @var Group $group */
        $group = $repository->getReference($reference);
        $this->deleteBankAccountWithWrongCredentialsThrowsException($group, $user);
    }

    public function testDeleteBankAccountIsOk()
    {
        $repository = $this->loadFixtures([
            LoadAccountGroupData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $this->deleteBankAccountIsOk($group);
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
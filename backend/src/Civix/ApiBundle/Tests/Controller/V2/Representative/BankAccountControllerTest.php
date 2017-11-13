<?php
namespace Civix\ApiBundle\Tests\Controller\V2\Representative;

use Civix\ApiBundle\Tests\Controller\V2\BankAccountControllerTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Stripe\LoadAccountRepresentativeData;

class BankAccountControllerTest extends BankAccountControllerTestCase
{
	const API_ENDPOINT = '/api/v2/representatives/{root}/bank-accounts';

    protected function getApiEndpoint()
    {
        return self::API_ENDPOINT;
    }

	public function testGetBankAccountsWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('representative_jb');
		$this->getBankAccountsWithWrongCredentialsThrowsException($group, 'user2');
    }

    public function testGetBankAccountsIsOk()
    {
        $repository = $this->loadFixtures([
            LoadAccountRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        /** @var Account $account */
        $account = $repository->getReference('representative_account_1');
        $this->getBankAccountsIsOk($representative, $account);
    }

	public function testCreateBankAccountWithWrongCredentialsThrowsException()
	{
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createBankAccountWithWrongCredentialsThrowsException($representative, 'user2');
	}

    public function testCreateBankAccountWithWrongDataReturnsError()
    {
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createBankAccountWithWrongDataReturnsError($representative);
    }

	public function testCreateBankAccountIsOk()
	{
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createBankAccountIsOk($representative, UserRepresentative::class);
	}

	public function testCreateBankAccountWithExistentAccountIsOk()
	{
        $repository = $this->loadFixtures([
            LoadAccountRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->createBankAccountWithExistentAccountIsOk($representative);
	}

    public function testDeleteBankAccountWithWrongCredentialsThrowsException()
    {
        $repository = $this->loadFixtures([
            LoadUserRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->deleteBankAccountWithWrongCredentialsThrowsException($representative, 'user2');
    }

    public function testDeleteBankAccountIsOk()
    {
        $repository = $this->loadFixtures([
            LoadAccountRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var UserRepresentative $representative */
        $representative = $repository->getReference('representative_jb');
        $this->deleteBankAccountIsOk($representative);
    }
}
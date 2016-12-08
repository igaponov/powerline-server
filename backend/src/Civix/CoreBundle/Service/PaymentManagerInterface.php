<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\OfficialInterface;
use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Entity\Stripe\Card;
use Civix\CoreBundle\Entity\Stripe\CustomerInterface;

interface PaymentManagerInterface
{
    public function addBankAccount(LeaderContentRootInterface $root, BankAccount $bankAccount);

    public function deleteBankAccount(AccountInterface $account, BankAccount $bankAccount);

    public function addCard(OfficialInterface $official, Card $card);

    public function deleteCard(CustomerInterface $customer, Card $card);

    public function deleteAccount(AccountInterface $account);

    public function createAccount(LeaderContentRootInterface $root);

    public function createCustomer(OfficialInterface $user);
}
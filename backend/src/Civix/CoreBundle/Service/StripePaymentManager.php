<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\OfficialInterface;
use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Entity\Stripe\Card;
use Civix\CoreBundle\Entity\Stripe\CustomerInterface;

class StripePaymentManager implements PaymentManagerInterface
{
    /**
     * @var Stripe
     */
    private $stripe;

    public function __construct(Stripe $stripe)
    {
        $this->stripe = $stripe;
    }

    public function addBankAccount(LeaderContentRootInterface $root, BankAccount $bankAccount)
    {
        $account = $root->getStripeAccount();
        $this->stripe->addBankAccount($account, $bankAccount);
        $account->updateBankAccounts($this->stripe->getBankAccounts($account)->data);

        return $account;
    }

    public function deleteBankAccount(AccountInterface $account, BankAccount $bankAccount)
    {
        $this->stripe->removeBankAccount($account, $bankAccount);
        $account->updateBankAccounts($this->stripe->getBankAccounts($account)->data);
    }

    public function addCard(OfficialInterface $official, Card $card)
    {
        $customer = $official->getStripeCustomer();
        $this->stripe->addCard($customer, $card);
        $customer->updateCards($this->stripe->getCards($customer)->data);
    }

    public function deleteCard(CustomerInterface $customer, Card $card)
    {
        $this->stripe->removeCard($customer, $card);
        $customer->updateCards($this->stripe->getCards($customer)->data);
    }

    public function deleteAccount(AccountInterface $account)
    {
        $this->stripe->deleteAccount($account);
    }

    public function createAccount(LeaderContentRootInterface $root)
    {
        $account = $root->getStripeAccount();
        $response = $this->stripe->createAccount($root);
        $account
            ->setId($response->id)
            ->setSecretKey($response->keys->secret)
            ->setPublishableKey($response->keys->publishable)
        ;

        return $account;
    }

    public function createCustomer(OfficialInterface $user)
    {
        $customer = $user->getStripeCustomer();
        $response = $this->stripe->createCustomer($user);
        $customer->setId($response->id);

        return $customer;
    }
}
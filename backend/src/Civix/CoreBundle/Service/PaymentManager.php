<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\OfficialInterface;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\Stripe\CustomerInterface;
use Doctrine\ORM\EntityManager;
use Civix\CoreBundle\Entity\Stripe\Card;

class PaymentManager implements PaymentManagerInterface
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var PaymentManagerInterface
     */
    private $paymentManager;

    public function __construct(EntityManager $em, PaymentManagerInterface $paymentManager)
    {
        $this->em = $em;
        $this->paymentManager = $paymentManager;
    }

    public function addBankAccount(LeaderContentRootInterface $root, BankAccount $bankAccount)
    {
        if (!$root->getStripeAccount()) {
            $this->createAccount($root);
        }
        if (!$bankAccount->getEmail()) {
            $bankAccount->setEmail($root->getEmail());
        }

        $account = $this->paymentManager->addBankAccount($root, $bankAccount);

        $this->em->persist($account);
        $this->em->flush();

        return $account;
    }

    public function deleteBankAccount(AccountInterface $account, BankAccount $bankAccount)
    {
        $this->paymentManager->deleteBankAccount($account, $bankAccount);

        $this->em->persist($account);
        $this->em->flush();
    }

    public function addCard(OfficialInterface $official, Card $card)
    {
        $customer = $official->getStripeCustomer();
        if (!$customer) {
            $customer = $this->createCustomer($official);
        }

        $this->paymentManager->addCard($official, $card);

        $this->em->persist($customer);
        $this->em->flush();

        return $customer;
    }

    public function deleteCard(CustomerInterface $customer, Card $card)
    {
        $this->paymentManager->deleteCard($customer, $card);

        $this->em->persist($customer);
        $this->em->flush();
    }

    public function deleteAccount(AccountInterface $account)
    {
        $this->paymentManager->deleteAccount($account);

        $this->em->remove($account);
        $this->em->flush();
    }

    public function createAccount(LeaderContentRootInterface $root)
    {
        $account = new Account();
        $root->setStripeAccount($account);

        $this->paymentManager->createAccount($root);

        $this->em->persist($root);
        $this->em->flush();

        return $account;
    }

    public function createCustomer(OfficialInterface $user)
    {
        $customer = new Customer();
        $user->setStripeCustomer($customer);

        $this->paymentManager->createCustomer($user);

        $this->em->persist($user);
        $this->em->flush();

        return $customer;
    }
}
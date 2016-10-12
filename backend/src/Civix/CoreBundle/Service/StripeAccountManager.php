<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Stripe\AccountGroup;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\Stripe\CustomerUser;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Event\AccountEvent;
use Civix\CoreBundle\Event\AccountEvents;
use Civix\CoreBundle\Event\BankAccountEvent;
use Civix\CoreBundle\Event\CardEvent;
use Civix\CoreBundle\Event\CustomerEvent;
use Civix\CoreBundle\Event\CustomerEvents;
use Doctrine\ORM\EntityManager;
use Civix\CoreBundle\Entity\Stripe\Card;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StripeAccountManager
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManager $em,
        EventDispatcherInterface $dispatcher
    ) {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function addBankAccount(UserInterface $user, BankAccount $bankAccount)
    {
        $account = $this->em
            ->getRepository(AccountGroup::class)
            ->findOneBy(['user' => $user])
        ;
        if (!$account) {
            $account = $this->createAccount($user);
        }
        if (!$bankAccount->getEmail()) {
            $bankAccount->setEmail($user->getEmail());
        }

        $event = new BankAccountEvent($account, $bankAccount);
        $this->dispatcher->dispatch(AccountEvents::BANK_ACCOUNT_PRE_CREATE, $event);

        $this->em->persist($account);
        $this->em->flush();

        return $account;
    }

    public function addCard(UserInterface $user, Card $card)
    {
        $customer = $this->em
            ->getRepository(CustomerUser::class)
            ->findOneBy(['user' => $user])
        ;
        if (!$customer) {
            $customer = $this->createCustomer($user);
        }

        $event = new CardEvent($customer, $card);
        $this->dispatcher->dispatch(CustomerEvents::CARD_PRE_CREATE, $event);

        $this->em->persist($customer);
        $this->em->flush();

        return $customer;
    }

    public function deleteCard(Customer $customer, Card $card)
    {
        $event = new CardEvent($customer, $card);
        $this->dispatcher->dispatch(CustomerEvents::CARD_PRE_DELETE, $event);

        $this->em->persist($customer);
        $this->em->flush();
    }

    private function createAccount(UserInterface $group)
    {
        $account = new AccountGroup();
        $account->setUser($group);

        $event = new AccountEvent($account);
        $this->dispatcher->dispatch(AccountEvents::PRE_CREATE, $event);

        $this->em->persist($account);
        $this->em->flush($account);

        return $account;
    }

    private function createCustomer(UserInterface $user)
    {
        $customer = new CustomerUser();
        $customer->setUser($user);

        $event = new CustomerEvent($customer);
        $this->dispatcher->dispatch(CustomerEvents::PRE_CREATE, $event);

        $this->em->persist($customer);
        $this->em->flush();

        return $customer;
    }
}
<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Stripe\AccountGroup;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Event\AccountEvent;
use Civix\CoreBundle\Event\AccountEvents;
use Civix\CoreBundle\Event\BankAccountEvent;
use Doctrine\ORM\EntityManager;
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
}
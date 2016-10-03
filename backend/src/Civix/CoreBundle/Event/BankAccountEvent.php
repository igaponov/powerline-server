<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;

class BankAccountEvent extends AccountEvent
{
    /**
     * @var BankAccount
     */
    private $bankAccount;

    public function __construct(AccountInterface $account, BankAccount $bankAccount)
    {
        parent::__construct($account);
        $this->bankAccount = $bankAccount;
    }

    /**
     * @return BankAccount
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }
}
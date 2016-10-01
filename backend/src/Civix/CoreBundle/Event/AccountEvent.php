<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Symfony\Component\EventDispatcher\Event;

class AccountEvent extends Event
{
    /**
     * @var AccountInterface
     */
    private $account;

    public function __construct(AccountInterface $account)
    {
        $this->account = $account;
    }

    /**
     * @return AccountInterface
     */
    public function getAccount()
    {
        return $this->account;
    }
}
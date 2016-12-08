<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\Account;
use Doctrine\ORM\Mapping as ORM;

trait HasStripeAccountTrait
{
    /**
     * @var Account
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\Account", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $stripeAccount;

    /**
     * @return Account
     */
    public function getStripeAccount()
    {
        return $this->stripeAccount;
    }

    /**
     * @param Account $stripeAccount
     * @return $this
     */
    public function setStripeAccount(Account $stripeAccount)
    {
        $this->stripeAccount = $stripeAccount;

        return $this;
    }
}
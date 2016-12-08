<?php
namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Stripe\AccountInterface;
use Doctrine\ORM\Mapping as ORM;

trait HasStripeAccountTrait
{
    /**
     * @var AccountInterface
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Stripe\AccountInterface", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $stripeAccount;

    /**
     * @return AccountInterface
     */
    public function getStripeAccount()
    {
        return $this->stripeAccount;
    }

    /**
     * @param AccountInterface $stripeAccount
     * @return $this
     */
    public function setStripeAccount(AccountInterface $stripeAccount)
    {
        $this->stripeAccount = $stripeAccount;

        return $this;
    }
}
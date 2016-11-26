<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Doctrine\ORM\Mapping as ORM;
use Civix\CoreBundle\Entity\Representative;

/**
 * @ORM\Entity
 */
class AccountRepresentative extends Account
{
    /**
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Representative")
     * @ORM\JoinColumn(name="representative_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $representative;

    public function getRoot()
    {
        return $this->getRepresentative();
    }

    public function setRoot(LeaderContentRootInterface $root)
    {
        return $this->setRepresentative($root);
    }

    /**
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function getRepresentative()
    {
        return $this->representative;
    }

    /**
     * @param mixed $user
     * @return $this
     */
    public function setRepresentative(Representative $user)
    {
        $this->representative = $user;

        return $this;
    }
}

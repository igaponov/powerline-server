<?php

namespace Civix\CoreBundle\Entity\Stripe;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Doctrine\ORM\Mapping as ORM;
use Civix\CoreBundle\Entity\Group;

/**
 * @ORM\Entity
 */
class AccountGroup extends Account
{
    /**
     * @var Group
     * @ORM\OneToOne(targetEntity="\Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $group;

    public function getRoot()
    {
        return $this->getGroup();
    }

    public function setRoot(LeaderContentRootInterface $root)
    {
        return $this->setGroup($root);
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     * @return AccountGroup
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;

        return $this;
    }
}

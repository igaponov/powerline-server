<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class KarmaRepository extends EntityRepository
{
    public function findOneByUserAndType(User $user, int $type)
    {
        return $this->findOneBy([
            'user' => $user,
            'type' => $type,
        ]);
    }
}
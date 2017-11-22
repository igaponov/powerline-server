<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class RecoveryTokenRepository extends EntityRepository
{
    public function findOneActiveByToken($token)
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->setParameter(':token', $token)
            ->andWhere('t.expireDate > :date')
            ->setParameter(':date', new \DateTime())
            ->andWhere('t.confirmedAt IS NULL')
            ->getQuery()->getOneOrNullResult();
    }
}
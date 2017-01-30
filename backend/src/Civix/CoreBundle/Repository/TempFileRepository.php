<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TempFileRepository extends EntityRepository
{
    public function findOneByExpiredAt($id)
    {
        return $this->createQueryBuilder('f')
            ->where('f.id = :id')
            ->setParameter(':id', $id)
            ->andWhere('f.expiredAt > CURRENT_TIMESTAMP()')
            ->getQuery()->getOneOrNullResult();
    }
}
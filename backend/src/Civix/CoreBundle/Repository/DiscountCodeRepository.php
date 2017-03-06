<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class DiscountCodeRepository extends EntityRepository
{
    public function findOriginalCodeByUserAndCode($code)
    {
        return $this->createQueryBuilder('dc')
            ->where('dc.code = :code')
            ->setParameter(':code', $code)
            ->getQuery()->getOneOrNullResult();
    }
}
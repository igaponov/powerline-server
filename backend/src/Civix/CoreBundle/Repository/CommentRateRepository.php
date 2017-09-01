<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\BaseComment;
use Doctrine\ORM\EntityRepository;

class CommentRateRepository extends EntityRepository
{
    /**
     * Return rateSum and rateCount for comment
     *
     * @param BaseComment $comment
     * @return array [rateSum, rateCount]
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getRateStatistics(BaseComment $comment): array
    {
        $query = $this->createQueryBuilder('r')
            ->select('SUM(r.rateValue) AS rateSum', 'SUM(ABS(r.rateValue)) AS rateCount')
            ->where('r.comment = :comment')
            ->setParameter(':comment', $comment)
            ->getQuery();

        return $query->getSingleResult();
    }
}

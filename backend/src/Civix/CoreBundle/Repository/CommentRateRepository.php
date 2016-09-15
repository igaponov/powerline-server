<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class CommentRateRepository extends EntityRepository
{
    /**
     * @param BaseComment $comment
     * @param User $user
     * @param BaseCommentRate $rateComment
     * @return BaseCommentRate
     * @deprecated
     */
    public function addRateToComment(BaseComment $comment, User $user, BaseCommentRate $rateComment)
    {
        $rateComment->setComment($comment);
        $rateComment->setUser($user);

        return $rateComment;
    }

    /**
     * @param BaseComment $comment
     * @return mixed
     * @deprecated Use {@link getRateStatistics}
     */
    public function calcRateCommentSum(BaseComment $comment)
    {
        return $this->createQueryBuilder('cr')
            ->select('SUM(cr.rateValue) as rateSum')
            ->where('cr.comment = :comment')
            ->setParameter('comment', $comment)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Return rateSum and rateCount for comment
     *
     * @param BaseComment $comment
     * @return array [rateSum, rateCount]
     */
    public function getRateStatistics(BaseComment $comment)
    {
        $query = $this->createQueryBuilder('r')
            ->select('SUM(r.rateValue) AS rateSum', 'SUM(ABS(r.rateValue)) AS rateCount')
            ->where('r.comment = :comment')
            ->setParameter(':comment', $comment)
            ->getQuery();

        return $query->getSingleResult();
    }
}

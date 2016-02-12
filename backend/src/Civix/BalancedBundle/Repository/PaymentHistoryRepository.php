<?php

namespace Civix\BalancedBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\BalancedBundle\Entity\PaymentHistory;

class PaymentHistoryRepository extends EntityRepository
{
    public function findNotPaidOut(Question $question)
    {
        return $this->createQueryBuilder('ph')
            ->where('ph.question_id = :question')
            ->andWhere('ph.state = :state')
            ->andWhere('ph.paidOut IS NULL')
            ->andWhere('ph.toUser IS NOT NULL')
            ->setParameter('state', PaymentHistory::STATE_SUCCESS)
            ->setParameter('question', $question->getId())
            ->getQuery()->getResult()
        ;
    }
}

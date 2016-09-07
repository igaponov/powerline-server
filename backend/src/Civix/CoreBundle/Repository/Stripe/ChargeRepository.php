<?php

namespace Civix\CoreBundle\Repository\Stripe;

use Civix\CoreBundle\Entity\Poll\Question;
use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\Stripe\Charge;

class ChargeRepository extends EntityRepository
{
    public function getAmountForPaymentRequest(Question $question)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('SUM(c.amount) as amount')
            ->from(Charge::class, 'c')
            ->where('c.question = :question')
            ->andWhere('c.status = :status')
            ->setParameter('question', $question)
            ->setParameter('status', 'succeeded')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}

<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class CountPriorityActivities
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(User $user, \DateTime $startAt): int
    {
        $builder = new ActivitiesQueryBuilder($this->em);
        $qb = $builder($user, null);
        $query = $qb
            ->select('COUNT(act)')
            // prioritized
            ->andWhere('
            (act.expireAt > CURRENT_TIMESTAMP() OR act.expireAt IS NULL)
            AND ('.implode(' OR ', $builder->getTypeCases()).')
            ')
            ->andWhere('act.sentAt > :startAt')
            ->setParameter('startAt', $startAt->format('Y-m-d H:i:s'))
            ->getQuery();

        return (int)$query->getSingleScalarResult();
    }
}
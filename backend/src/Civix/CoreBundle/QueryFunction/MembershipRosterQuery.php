<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Group;

class MembershipRosterQuery extends AbstractUserReportQuery
{
    public function __invoke(Group $group) : array
    {
        $permissions = $group->getRequiredPermissions();
        $qb = $this->createQueryBuilder($permissions);
        $qb->where('mr.group = :group')
            ->setParameter(':group', $group->getId())
            ->groupBy('u.id');

        return $qb->getQuery()->getArrayResult();
    }
}
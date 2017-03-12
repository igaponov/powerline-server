<?php

namespace Civix\CoreBundle\Repository\Report;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class MembershipReportRepository extends EntityRepository
{
    public function getMembershipReport(Group $group)
    {
        return $this->createQueryBuilder('mr')
            ->where('mr.group = :group')
            ->setParameter(':group', $group->getId())
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function upsertMembershipReport(User $user, Group $group, $groupFields)
    {
        return $this->getEntityManager()->getConnection()
            ->executeQuery('
                    REPLACE INTO membership_report(user_id, group_id, group_fields) 
                    VALUES (
                        ?1,
                        ?2,
                        COALESCE(?3, (SELECT group_fields FROM membership_report WHERE user_id = ?1 AND group_id = ?2), ?4)
                    )
                ', [
                $user->getId(),
                $group->getId(),
                $groupFields ? json_encode($groupFields) : null,
                '{}'
            ])
            ->execute();
    }

    public function deleteMembershipReport(User $user, Group $group)
    {
        return $this->getEntityManager()->getConnection()
            ->executeQuery('DELETE FROM membership_report WHERE user_id = ? AND group_id = ?')
            ->execute([$user->getId(), $group->getId()]);
    }
}
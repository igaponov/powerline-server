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
            ->executeQuery("
                    REPLACE INTO membership_report(user_id, group_id, group_fields) 
                    VALUES (
                        :user,
                        :group,
                        COALESCE(:fields, (SELECT group_fields FROM (SELECT group_fields FROM membership_report WHERE user_id = :user AND group_id = :group) AS temp), '{}')
                    )
                ", [
                ':user' => $user->getId(),
                ':group' => $group->getId(),
                ':fields' => $groupFields ? json_encode($groupFields) : null,
            ])
            ->rowCount();
    }

    public function deleteMembershipReport(User $user, Group $group)
    {
        return $this->getEntityManager()->getConnection()
            ->delete('membership_report', [
                'user_id' => $user->getId(),
                'group_id' => $group->getId(),
            ]);
    }
}
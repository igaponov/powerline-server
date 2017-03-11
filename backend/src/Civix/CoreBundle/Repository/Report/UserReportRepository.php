<?php

namespace Civix\CoreBundle\Repository\Report;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class UserReportRepository extends EntityRepository
{
    public function getUserReport(User $user)
    {
        return $this->createQueryBuilder('ur')
            ->where('ur.user = :user')
            ->setParameter(':user', $user->getId())
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function upsertUserReport(User $user, int $followers = null, array $representatives = null)
    {
        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                'REPLACE INTO user_report(user_id, followers, representatives) 
                VALUES (
                    ?1,
                    COALESCE(?2, (SELECT followers FROM user_report WHERE user_id = ?1), ?4),
                    COALESCE(?3, (SELECT representatives FROM user_report WHERE user_id = ?1), ?5)
                )', [
                    $user->getId(),
                    $followers,
                    $representatives ? json_encode($representatives) : null,
                    0,
                    '[]',
            ])
            ->execute();
    }
}
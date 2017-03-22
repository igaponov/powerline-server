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
                "REPLACE INTO user_report(user_id, followers, representatives) 
                VALUES (
                    :id,
                    COALESCE(:followers, (SELECT followers FROM user_report WHERE user_id = :id), 0),
                    COALESCE(:representatives, (SELECT representatives FROM user_report WHERE user_id = :id), '[]')
                )", [
                    ':id' => $user->getId(),
                    ':followers' => $followers,
                    ':representatives' => $representatives ? json_encode($representatives) : null,
            ])
            ->execute();
    }
}
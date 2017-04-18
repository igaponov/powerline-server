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

    public function upsertUserReport(
        User $user,
        int $followers = null,
        array $representatives = null,
        string $country = null,
        string $state = null,
        string $locality = null,
        array $districts = null
    ) {
        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                "REPLACE INTO user_report(user_id, followers, representatives, country, state, locality, districts) 
                SELECT 
                    :id, 
                    COALESCE(:followers, ur.followers, 0),
                    COALESCE(:representatives, ur.representatives, '[]'),
                    COALESCE(:country, ur.country, ''),
                    COALESCE(:state, ur.state, ''),
                    COALESCE(:locality, ur.locality, ''),
                    COALESCE(:districts, ur.districts, '[]')
                FROM user u
                LEFT JOIN user_report ur ON ur.user_id = u.id
                WHERE u.id = :id
                ", [
                    ':id' => $user->getId(),
                    ':followers' => $followers,
                    ':representatives' => $representatives ? json_encode($representatives) : null,
                    ':country' => $country,
                    ':state' => $state,
                    ':locality' => $locality,
                    ':districts' => $districts ? json_encode($districts) : null,
            ])
            ->execute();
    }

    public function updateUserReportKarma(User $user)
    {
         return $this->getEntityManager()->getConnection()
            ->executeUpdate(
                'UPDATE user_report
                SET karma = (SELECT SUM(points) FROM karma k WHERE k.user_id = :user)
                WHERE user_id = :user',
                [':user' => $user->getId()]
            );
    }
}
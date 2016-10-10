<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\User;

class SocialActivityRepository extends EntityRepository
{
    public function findFollowingForUser(User $user)
    {
        $userFollowingIds = $user->getFollowingIds();
        if (empty($userFollowingIds)) {
            return [];
        }
        $activeGroups = $this->getEntityManager()->getRepository('CivixCoreBundle:UserGroup')->getActiveGroupIds($user);

        $qb = $this->createQueryBuilder('sa');

        $qb
            ->addSelect('f')
            ->addSelect('g')
            ->leftJoin('sa.following', 'f')
            ->leftJoin('sa.group', 'g')
            ->where($qb->expr()->andX(
                'sa.recipient is NULL',
                $qb->expr()->in('sa.following', ':followings'),
                empty($activeGroups) ? 'sa.group is NULL' : 'sa.group is NULL OR sa.group IN (:groups)'
            ))
            ->setParameter('followings', $userFollowingIds)
            ->setParameter('groups', $activeGroups)
            ->orderBy('sa.id', 'DESC')
            ->setMaxResults(200)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findByRecipient(User $user)
    {
        return $this->createQueryBuilder('sa')
            ->addSelect('f')
            ->addSelect('g')
            ->leftJoin('sa.following', 'f')
            ->leftJoin('sa.group', 'g')
            ->where('sa.recipient = :recipient')
            ->setParameter('recipient', $user)
            ->orderBy('sa.id', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getFilteredByFollowingAndRecipientQuery(User $user, $following = false)
    {
        $qb = $this->createQueryBuilder('sa')
            ->select('sa', 'f', 'g')
            ->leftJoin('sa.following', 'f')
            ->leftJoin('sa.group', 'g')
            ->orderBy('sa.id', 'DESC');
        $exprBuilder = $qb->expr();
        if ($following) {
            $userFollowingIds = $user->getFollowingIds();
            $activeGroups = $this->getEntityManager()
                ->getRepository('CivixCoreBundle:UserGroup')
                ->getActiveGroupIds($user);
            $expr = $exprBuilder->andX('sa.recipient is NULL');
            if (empty($activeGroups)) {
                $expr->add('sa.group is NULL');
            } else {
                $expr->add($exprBuilder->in('sa.group', $activeGroups));
            }
            if (empty($userFollowingIds)) {
                $expr->add('sa.following is NULL');
            } else {
                $expr->add($exprBuilder->orX(
                    'sa.following is NULL',
                    $exprBuilder->in('sa.following', $userFollowingIds)
                ));
            }
            $qb->where($expr);
        } else {
            $qb->where('sa.recipient = :user')
                ->setParameter(':user', $user);
        }
        
        return $qb->getQuery();
    }
}

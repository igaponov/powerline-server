<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Query\Expr;

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

    public function getFilteredByFollowingAndRecipientQuery(User $user, $tab = null)
    {
        $qb = $this->createQueryBuilder('sa')
            ->select('sa', 'f', 'g')
            ->leftJoin('sa.following', 'f')
            ->leftJoin('sa.group', 'g')
            ->orderBy('sa.id', 'DESC');
        $where = new Expr\Orx();
        if (($tab === 'following' || !$tab) && $expr = $this->getExprForFollowingTab($user)) {
            $where->add($expr);
        }
        if (($tab === 'you' || !$tab) && $expr = $this->getExprForYouTab($user)) {
            $where->add($expr);
        }
        if ($where->count()) {
            return $qb->where($where)
                ->getQuery();
        } else {
            return [];
        }
    }

    private function getExprForYouTab(User $user)
    {
        $exprBuilder = new Expr();
        return $exprBuilder->eq('sa.recipient', $user->getId());
    }

    private function getExprForFollowingTab(User $user)
    {
        $exprBuilder = new Expr();
        $userFollowingIds = $user->getFollowingIds();
        $activeGroups = $this->getEntityManager()
            ->getRepository('CivixCoreBundle:UserGroup')
            ->getActiveGroupIds($user);
        $expr = $exprBuilder->andX('sa.recipient is NULL');
        if (empty($activeGroups)) {
            return null;
        } else {
            $expr->add($exprBuilder->in('sa.group', $activeGroups));
        }
        if (empty($userFollowingIds)) {
            return null;
        } else {
            $expr->add($exprBuilder->in('sa.following', $userFollowingIds));
        }

        return $expr;
    }
}

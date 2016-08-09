<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Doctrine\ORM\EntityRepository;

/**
 * UserFollowRepository.
 */
class UserFollowRepository extends EntityRepository
{
    public function getFollowersByFStatus($user, $status)
    {
        return $this->createQueryBuilder('uf')
                ->where('uf.user = :user')
                ->andWhere('uf.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', $status)
                ->orderBy('uf.dateCreate', 'asc')
                ->getQuery()
                ->getResult();
    }

    public function getFollowingByFStatus($user, $status)
    {
        return $this->createQueryBuilder('uf')
                ->where('uf.follower = :user')
                ->andWhere('uf.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', $status)
                ->orderBy('uf.dateCreate', 'asc')
                ->getQuery()
                ->getResult();
    }

    public function getFollowingByUser($user)
    {
        return $this->createQueryBuilder('uf')
            ->where('uf.follower = :user')
            ->setParameter('user', $user)
            ->orderBy('uf.dateCreate', 'asc')
            ->getQuery()
            ->getResult();
    }

    /**
     * @deprecated
     */
    public function getLastApprovedFollowing($follower, $lastApprovedDate)
    {
        return $this->createQueryBuilder('uf')
                ->where('uf.follower = :follower')
                ->andWhere('uf.status = :status')
                ->andWhere('uf.dateApproval >= :approvedDate')
                ->setParameter('follower', $follower)
                ->setParameter('status', UserFollow::STATUS_ACTIVE)
                ->setParameter('approvedDate', $lastApprovedDate)
                ->orderBy('uf.dateApproval', 'desc')
                ->getQuery()
                ->getResult();
    }

    public function getFindByUserQuery(User $user)
    {
        return $this->createQueryBuilder('uf')
            ->select('uf, f')
            ->leftJoin('uf.follower', 'f')
            ->where('uf.user = :user AND uf.status = :active')
            ->orWhere('uf.user = :user AND uf.status = :pending AND uf.dateCreate > :pendingStart')
            ->setParameter('active', UserFollow::STATUS_ACTIVE)
            ->setParameter('pending', UserFollow::STATUS_PENDING)
            ->setParameter('pendingStart', new \DateTime('-6 months'))
            ->setParameter('user', $user)
            ->orderBy('uf.dateCreate', 'DESC')
            ->getQuery()
        ;
    }

    public function getFindByFollowerQuery(User $follower)
    {
        return $this->createQueryBuilder('uf')
            ->select('uf, u')
            ->leftJoin('uf.user', 'u')
            ->where('uf.follower = :user AND uf.status = :active')
            ->orWhere('uf.follower = :user AND uf.status = :pending AND uf.dateCreate > :pendingStart')
            ->setParameter('active', UserFollow::STATUS_ACTIVE)
            ->setParameter('pending', UserFollow::STATUS_PENDING)
            ->setParameter('pendingStart', new \DateTime('-6 months'))
            ->setParameter('user', $follower)
            ->orderBy('uf.dateCreate', 'DESC')
            ->getQuery()
        ;
    }

    public function findActiveFollower(User $user, User $follower)
    {
        return $this->findOneBy([
            'user' => $user,
            'follower' => $follower,
            'status' => UserFollow::STATUS_ACTIVE,
        ]);
    }

    public function handle(UserFollow $follow)
    {
        /* @var $entity UserFollow */
        $entity = $this->findOneBy(['user' => $follow->getUser(), 'follower' => $follow->getFollower()]);

        $entity = $entity ?: $follow;
        $entity->setStatus($follow->getStatus())
            ->setDateCreate(new \DateTime())
        ;

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush($entity);

        return $entity;
    }

    public function followGroupMembers(User $user, Group $group)
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery('
                INSERT INTO users_follow(user_id, follower_id, date_create, status) 
                SELECT ug.user_id, :user, current_timestamp, :status FROM users_groups ug 
                LEFT JOIN users_follow uf ON uf.user_id = ug.user_id
                WHERE ug.group_id = :group AND uf.id IS NULL', [
                    ':user' => $user->getId(),
                    ':status' => UserFollow::STATUS_PENDING,
                    ':group' => $group->getId(),
                ]
            )->execute();
    }
}

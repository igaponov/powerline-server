<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;

class UserGroupRepository extends EntityRepository
{
    public function getUsersByGroupQuery(Group $group, $status = null)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('gu, gr')
            ->from('CivixCoreBundle:UserGroup', 'gu')
            ->leftJoin('gu.group', 'gr')
            ->where('gu.group = :group')
            ->setParameter('group', $group);

        if (!is_null($status)) {
            $query
                ->andWhere('gu.status = :status')
                ->setParameter('status', $status);
        }
        $query
            ->orderBy('gu.createdAt', 'asc')
            ->getQuery();

        return $query;
    }

    public function setApprovedAllUsersInGroup(Group $group)
    {
        $this->getEntityManager()
            ->createQuery('UPDATE CivixCoreBundle:UserGroup gu
                              SET gu.status = :status
                            WHERE gu.group = :group
                              AND gu.status <> :status')
            ->setParameter('status', UserGroup::STATUS_ACTIVE)
            ->setParameter('group', $group)
            ->execute();
    }

    /**
     * @param Group $group
     * @param User  $user
     *
     * @return UserGroup|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isJoinedUser(Group $group, User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()
                ->select('gu')
                ->from('CivixCoreBundle:UserGroup', 'gu')
                ->where('gu.user = :user')
                ->andWhere('gu.group = :group')
                ->setParameter('user', $user)
                ->setParameter('group', $group)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function getSubQueryGroupByJoinStatus()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from('CivixCoreBundle:Group', 'g')
            ->innerJoin('g.users', 'gu')
            ->where('gu.user = :user AND gu.status = :joinSubqueryStatus');
    }

    public function getActiveGroupIds(User $user)
    {
        $userGroups = $this->findBy([
            'user' => $user,
            'status' => UserGroup::STATUS_ACTIVE,
        ]);

        return array_map(function (UserGroup $userGroup) {
            return $userGroup->getGroup()->getId();
        }, $userGroups);
    }

    public function getMembershipReportQuery(Group $group, $status = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('gu, gr, u')
            ->addSelect('(SELECT gs.title FROM CivixCoreBundle:GroupSection gs LEFT JOIN gs.users us WHERE gs.group = gr AND us = u) as groupDivision')
            ->from('CivixCoreBundle:UserGroup', 'gu')
            ->leftJoin('gu.group', 'gr')
            ->leftJoin('gu.user', 'u')
            ->where('gu.group = :group')
            ->setParameter('group', $group);

        if (!is_null($status)) {
            $queryBuilder
                ->andWhere('gu.status = :status')
                ->setParameter('status', $status);
        }
        $queryBuilder
            ->orderBy('gu.createdAt', 'asc');

        return $queryBuilder->getQuery();
    }

    public function getGeoUserGroups(User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('ug')
            ->from(UserGroup::class, 'ug')
            ->leftJoin('ug.group', 'g')
            ->where('ug.user = :user AND g.groupType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('types', [Group::GROUP_TYPE_LOCAL, Group::GROUP_TYPE_STATE, Group::GROUP_TYPE_COUNTRY])
            ->getQuery()->getResult();
    }

    public function getFindByGroupQuery(Group $group)
    {
        return $this->createQueryBuilder('ug')
            ->select('ug', 'u', 'g', 'gm')
            ->innerJoin('ug.user', 'u')
            ->leftJoin('ug.group', 'g')
            ->leftJoin('g.managers', 'gm')
            ->where('ug.group = :group')
            ->setParameter('group', $group)
            ->orderBy('u.id', 'ASC')
            ->getQuery();
    }

    public function getOldestMember(Group $group)
    {
        return $this->createQueryBuilder('ug')
            ->where('ug.group = :group')
            ->setParameter(':group', $group)
            ->andWhere('ug.user != :user')
            ->setParameter(':user', $group->getOwner())
            ->orderBy('ug.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTotalMembers(Group $group)
    {
        return (int)$this->createQueryBuilder('ug')
            ->select('COUNT(ug)')
            ->where('ug.group = :group')
            ->setParameter(':group', $group)
            ->getQuery()->getSingleScalarResult();
    }
}

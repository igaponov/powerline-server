<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\DBAL\Driver\Statement;
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

    /**
     * @param Group $group
     * @return Statement
     */
    public function getFindMembersWithRequiredFieldsQuery(Group $group)
    {
        $fields = $group->getFields();
        $qb = $this->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->select('u.firstName AS first_name, u.lastName AS last_name, u.address1, u.address2, u.city, u.state, u.country, u.zip, u.email, u.phone, u.bio, u.slogan, CASE WHEN u.facebook_id IS NOT NULL THEN 1 ELSE 0 END AS facebook, (SELECT COUNT(f.id) FROM users_follow f WHERE f.user_id = u.id) AS followers')
            ->from('users_groups', 'ug')
            ->leftJoin('ug', 'user', 'u', 'ug.user_id = u.id')
            ->where('ug.group_id = :group')
            ->setParameter(':group', $group->getId())
            ->groupBy('u.id');
        $platform = $this->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();
        foreach ($fields as $k => $field) {
            $qb->addSelect("v$k.field_value AS {$platform->quoteSingleIdentifier($field->getFieldName())}")
                ->leftJoin('u', 'groups_fields_values', 'v'.$k, "v$k.user_id = u.id AND v$k.field_id = :field$k")
                ->setParameter(":field$k", $field->getId());
        }
        $qb->addSelect('r.president, r.vice_president, r.senator1, r.senator2, r.congressman')
            ->leftJoin('u', 'user_representative_report', 'r', 'r.user_id = u.id');

        return $qb->execute();
    }
}

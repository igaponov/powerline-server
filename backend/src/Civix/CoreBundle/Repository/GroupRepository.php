<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;

class GroupRepository extends EntityRepository
{
    /**
     *
     * @return array
     */
    public function getGroupsByUser()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
                ->select('gr')
                ->from('CivixCoreBundle:Group', 'gr')
                ->getQuery()
                ->getResult();
    }

    /**
     *
     * @param User $user
     * @return \Doctrine\ORM\Query
     */
    public function getByUserQuery(User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb
            ->select('ug, g, gm')
            ->from(UserGroup::class, 'ug')
            ->leftJoin('ug.group', 'g')
            ->leftJoin('g.managers', 'gm', 'WITH', 'gm.user = :user')
            ->where('ug.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
        ;
    }

    /**
     * 
     * @param User $user
     */
    public function getPopularGroupsByUser(User $user)
    {
        $groupOfUserIds = $user->getGroupsIds();

        $qb = $this->getEntityManager()
            ->createQueryBuilder();

        $qb->select('g, COUNT(u) AS HIDDEN count_users')
            ->from('CivixCoreBundle:Group', 'g')
            ->leftJoin('g.users', 'u')
            ->where('g.groupType = :type')
            ->setParameters(array(
                'type' => Group::GROUP_TYPE_COMMON,
            ))
            ->groupBy('g')
            ->orderBy('count_users', 'DESC')
            ->setMaxResults(5)
        ;
        if (!empty($groupOfUserIds)) {
            $qb->andWhere('g.id NOT IN (:ids)')
                ->setParameter('ids', $groupOfUserIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Fetch the groups of common type from 7 days ago to the current
     * moment and check if the current user belongs in each groups
     * for display as groups news results.
     * 
     * @param User $user
     */
    public function getNewGroupsByUser(User $user)
    {
        $groupOfUserIds = $user->getGroupsIds();

        $qb = $this->getEntityManager()
        ->createQueryBuilder();

        $limitDate = new \DateTime('NOW');
        $limitDate->sub(new \DateInterval('P7D'));
        $qb->select('g')
            ->from('CivixCoreBundle:Group', 'g')
            ->leftJoin('g.users', 'u')
            ->where('g.groupType = :type')
            ->andWhere('g.createdAt > :limit_date')
            ->setParameters(array(
                'type' => Group::GROUP_TYPE_COMMON,
                'limit_date' => $limitDate,
            ))
            ->orderBy('g.createdAt', 'DESC')
        ;
        if (!empty($groupOfUserIds)) {
            $qb->andWhere('g.id NOT IN (:ids)')
            ->setParameter('ids', $groupOfUserIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 
     * @param unknown $type
     * @param string $order
     */
    public function getQueryGroupOrderedById($type = Group::GROUP_TYPE_COMMON, $order = 'DESC')
    {
        return $this->createQueryBuilder('g')
                ->where('g.groupType = :type')
                ->setParameter('type', $type)
                ->orderBy('g.id', $order);
    }

    /**
     * 
     * @param Group $countryGroup
     */
    public function getQueryCountryGroupChildren(Group $countryGroup)
    {
        return $this->createQueryBuilder('g')
            ->where('g.parent = :parent')
            ->setParameter('parent', $countryGroup)
        ;
    }

    /**
     * 
     * @param Group $group
     */
    public function removeGroup(Group $group)
    {
        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Poll\Question\Group g')
            ->set('g.user', 'NULL')
            ->where('g.user = :groupId')
            ->setParameter('groupId', $group->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Activity a')
            ->set('a.group', 'NULL')
            ->where('a.group = :groupId')
            ->setParameter('groupId', $group->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()->getConnection()
                ->delete('users_groups', array('group_id' => $group->getId()));

        $this->getEntityManager()
            ->createQueryBuilder()
            ->delete('CivixCoreBundle:Group g')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $group->getId())
            ->getQuery()
            ->execute();
    }

    /**
     * 
     * @param unknown $id
     * @param unknown $type
     */
    public function getGroupByIdAndType($id, $type = Group::GROUP_TYPE_COMMON)
    {
        return $this->findOneBy(array(
            'id' => $id,
            'groupType' => $type,
        ));
    }

    /**
     * 
     * @param unknown $state
     */
    public function getLocalGroupsByState($state)
    {
        return $this->createQueryBuilder('g')
                ->where('g.groupType = :type')
                ->andWhere('g.localState = :state')
                ->setParameter('type', Group::GROUP_TYPE_LOCAL)
                ->setParameter('state', $state)
                ->getQuery()->getResult()
        ;
    }

    /**
     *
     * @param unknown $id
     * @param unknown $representativeId
     */
    public function getLocalGroupForRepr($id, $representativeId)
    {
        return $this->createQueryBuilder('gr')
            ->innerJoin('gr.localRepresentatives', 'repr')
            ->where('gr.id = :id')
            ->andWhere('gr.groupType = :type')
            ->andWhere('repr.id = :representativeId')
            ->setParameters(array(
                'id' => $id,
                'type' => Group::GROUP_TYPE_LOCAL,
                'representativeId' => $representativeId,
            ))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     *
     * @param string $query
     * @param User $user
     * @return array
     */
    public function findByQuery($query, User $user)
    {
        $qb = $this->createQueryBuilder('g');
        $qb->leftJoin('g.users', 'u')
            ->where('u.user = :user')
            ->andWhere($qb->expr()->like('g.officialName', $qb->expr()->literal('%'.$query.'%')))
            ->setParameter('user', $user)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $criteria Possible keys: `exclude_owned = User` - exclude groups of current user
     * @param array $orderBy Possible keys: created_at, popularity
     *
     * @return \Doctrine\ORM\Query
     */
    public function getFindByQuery($criteria, $orderBy)
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.users', 'ug')
            ->where('g.groupType = :type')
            ->setParameter('type', Group::GROUP_TYPE_COMMON)
            ->groupBy('g');

        if (isset($criteria['exclude_owned']) && $criteria['exclude_owned'] instanceof User) {
            $ids = $criteria['exclude_owned']->getGroupsIds();
            if ($ids) {
                $qb->andWhere(
                    $qb->expr()
                        ->notIn('g.id', $ids)
                );
            }
        }

        if (!empty($criteria['query'])) {
            $query = "%{$criteria['query']}%";
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('g.acronym', ':query'),
                    $qb->expr()->like('g.officialName', ':query')
                )
            )
            ->setParameter(':query', $query);
        }

        if (isset($orderBy['created_at'])) {
            $qb->orderBy('g.createdAt', $orderBy['created_at']);
        }
        if (isset($orderBy['popularity'])) {
            $qb->addSelect('COUNT(ug) AS HIDDEN count_users')
                ->orderBy('count_users', $orderBy['popularity']);
        }

        return $qb->getQuery();
    }

    /**
     * Returns not secret groups
     *
     * @param int $id
     * @return Group|null
     */
    public function findOneNotSecret($id)
    {
        $qb = $this->createQueryBuilder('g');
        $transparencies = [
            Group::GROUP_TRANSPARENCY_SECRET,
            Group::GROUP_TRANSPARENCY_TOP_SECRET,
        ];

        return $qb->where('g.id = :id')
            ->setParameter(':id', $id)
            ->andWhere($qb->expr()->notIn('g.transparency', $transparencies))
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @return \Doctrine\ORM\Query
     */
    public function getInvitesQuery(User $user)
    {
        return $this->createQueryBuilder('g')
            ->where(':user MEMBER OF g.invites')
            ->setParameter(':user', $user)
            ->getQuery();
    }

    public function findWithUser($criteria)
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g', 'ug')
            ->where('g.id = :id')
            ->setParameter(':id', $criteria['id']);
        if (!empty($criteria['owner'])) {
            $qb->leftJoin('g.users', 'ug', 'WITH', 'ug.user = :user')
                ->setParameter(':user', $criteria['owner']);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @return Group[]
     */
    public function getGeoGroupsByUser(User $user)
    {
        return $this->createQueryBuilder('g', 'g.id')
            ->addSelect('ug')
            ->leftJoin('g.users', 'ug')
            ->where('ug.user = :user')
            ->setParameter(':user', $user)
            ->andWhere('g.groupType IN (:types)')
            ->setParameter(':types', [
                Group::GROUP_TYPE_LOCAL,
                Group::GROUP_TYPE_STATE,
                Group::GROUP_TYPE_COUNTRY,
            ])
            ->getQuery()->getResult();
    }
}

<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Entity\User;

/**
 * RepresentativeRepository.
 */
class UserRepresentativeRepository extends EntityRepository
{
    /**
     * @param $status
     * @return Query
     */
    public function getQueryRepresentativeByStatus($status)
    {
        return $this->getQueryBuilderReprByStatus($status)
                ->getQuery();
    }

    public function getQueryBuilderReprByStatus($status, $excludeRepr = false)
    {
        $qBuilder = $this->createQueryBuilder('repr')
                ->where('repr.status = :status');

        //exclude representative from query
        if ($excludeRepr) {
            $qBuilder->andWhere('repr <> :currentRepr');
            $qBuilder->setParameter('currentRepr', $excludeRepr);
        }

        return $qBuilder
                ->setParameter('status', $status);
    }

    public function getOfficialTitles(UserRepresentative $excludeRepr = null)
    {
        $qBuilder = $this->createQueryBuilder('r')
                ->select('r.officialTitle')
                ->where('r.status = :status');

        //exclude representative from query
        if ($excludeRepr) {
            $qBuilder->andWhere('r <> :currentRepr');
            $qBuilder->setParameter('currentRepr', $excludeRepr);
        }

        return $qBuilder->addGroupBy('r.officialTitle')
                ->setParameter('status', UserRepresentative::STATUS_ACTIVE)
                ->getQuery()
                ->getResult();
    }

    public function getReprByDistrictsAndOffTitle($districts, $officialTitle)
    {
        return $this->createQueryBuilder('repr')
                ->where('repr.officialTitle = :officialTitle')
                ->andWhere('repr.district in (:districts)')
                ->setParameter('officialTitle', $officialTitle)
                ->setParameter('districts', $districts)
                ->getQuery()
                ->getResult();
    }

    public function getRepresentativeInformation($representativeId = 0, $ciceroId = 0)
    {
        $ciceroId = (int) $ciceroId;
        $representativeId = (int) $representativeId;

        if (0 < $representativeId) {
            $info = $this->createQueryBuilder('r')
                ->addSelect('u')
                ->innerJoin('r.user', 'u')
                ->where('r.id = :id')
                ->setParameter('id', $representativeId)
                ->getQuery()
                ->getOneOrNullResult();
        } elseif (0 < $ciceroId) {
            $info = $this->getEntityManager()->createQueryBuilder()
                ->select('r')
                ->from(Representative::class, 'r')
                ->where('r.id = :id')
                ->setParameter('id', $ciceroId)
                ->getQuery()
                ->getOneOrNullResult();
        } else {
            $info = null;
        }

        return $info;
    }

    public function getNonLegislativeRepresentative($districtsIds)
    {
        if (!$districtsIds) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->addSelect('u')
            ->innerJoin('r.user', 'u')
            ->where('r.isNonLegislative = 1')
            ->andWhere('r.district in (:districts)')
            ->setParameter('districts', $districtsIds)
            ->getQuery()
            ->getResult();
    }

    public function getQueryBuilderLocalRepr()
    {
        return $this->createQueryBuilder('repr');
    }

    public function findByQuery($query, User $user)
    {
        $userDistrictIds = $user->getDistrictsIds();

        $qb = $this->createQueryBuilder('r');
        $representatives = $qb->leftJoin('r.district', 'd')
            ->where($qb->expr()->in('r.district', $userDistrictIds ? $userDistrictIds : array(0)))
            ->andWhere('r.isNonLegislative = 1')
            ->andWhere($qb->expr()->like('r.officialTitle', $qb->expr()->literal('%'.$query.'%')))
            ->orderBy('d.districtType')
            ->getQuery()->getResult()
        ;

        return $representatives;
    }

    public function findByState($state)
    {
        return $this->findBy(['state' => $state]);
    }

    public function findByUpdatedAt($maxDateAt, $limit = null)
    {
        return $this->createQueryBuilder('r')
            ->where('r.updatedAt <= :date')
            ->setParameter('date', $maxDateAt)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return UserRepresentative[]
     */
    public function getByEmptyOpenStatesId()
    {
        return $this->createQueryBuilder('r')
            ->where('r.openstateId IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function getByUserQuery(User $user)
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter(':user', $user)
            ->getQuery();
    }

    public function isGroupRepresentative(Group $group, UserInterface $user)
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->where('r.localGroup = :group')
            ->setParameter(':group', $group)
            ->andWhere('r.user = :user')
            ->setParameter(':user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
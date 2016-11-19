<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\User;

/**
 * RepresentativeRepository.
 */
class RepresentativeRepository extends EntityRepository
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

    public function getOfficialTitles(Representative $excludeRepr = null)
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
                ->setParameter('status', Representative::STATUS_ACTIVE)
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
                ->where('r.id = :id')
                ->setParameter('id', $representativeId)
                ->getQuery()
                ->getOneOrNullResult();
        } elseif (0 < $ciceroId) {
            $info = $this->createQueryBuilder('r')
                ->where('r.storageId = :id')
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
        return $this->createQueryBuilder('repr')
            ->where('repr.isNonLegislative = 1')
            ->andWhere('repr.district in (:districts)')
            ->setParameter('districts', $districtsIds)
            ->getQuery()
            ->getResult();
    }

    public function getQueryBuilderLocalRepr($group)
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
     * @return Representative[]
     */
    public function getByEmptyOpenStatesId()
    {
        return $this->createQueryBuilder('r')
            ->where('r.openstateId IS NULL')
            ->getQuery()
            ->getResult();
    }
}

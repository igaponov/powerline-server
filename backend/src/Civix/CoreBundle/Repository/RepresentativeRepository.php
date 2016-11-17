<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\District;
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
     * @return \Doctrine\ORM\Query
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
            $qBuilder->andWhere('repr.id <> :currentRepr');
            $qBuilder->setParameter('currentRepr', $excludeRepr->getId());
        }

        return $qBuilder
                ->setParameter('status', $status);
    }

    public function getOfficialTitles($excludeRepr = false)
    {
        $qBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select('repr.officialTitle')
                ->from('CivixCoreBundle:Representative', 'repr')
                ->where('repr.status = :status');

        //exclude representative from query
        if ($excludeRepr) {
            $qBuilder->andWhere('repr.id <> :currentRepr');
            $qBuilder->setParameter('currentRepr', $excludeRepr->getId());
        }

        return $qBuilder->addGroupBy('repr.officialTitle')
                ->setParameter('status', Representative::STATUS_ACTIVE)
                ->getQuery()
                ->getResult();
    }

    public function getReprByDistrictsAndOffTitle($districts, $officialTitle)
    {
        return $this->createQueryBuilder('repr')
                ->where('repr.officialTitle = :officialTitle')
                ->andWhere('repr.districtId in (:districts)')
                ->setParameter('officialTitle', $officialTitle)
                ->setParameter('districts', $districts)
                ->getQuery()
                ->getResult();
    }

    public function cleanStorageIds()
    {
        return $this->getEntityManager()
                ->createQuery('UPDATE CivixCoreBundle:Representative repr
                                  SET repr.storageId = NULL
                                WHERE repr.storageId IS NOT NULL')
                ->execute();
    }

    public function getQueryRepresentativeOrderedById()
    {
        return $this->getEntityManager()
                ->createQuery('SELECT r FROM CivixCoreBundle:Representative r ORDER BY r.id DESC');
    }

    public function removeRepresentative(\Civix\CoreBundle\Entity\Representative $representative)
    {
        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Poll\Question\Representative r')
            ->set('r.user', 'NULL')
            ->where('r.user = :representativeId')
            ->setParameter('representativeId', $representative->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Activity a')
            ->set('a.representative', 'NULL')
            ->where('a.representative = :representativeId')
            ->setParameter('representativeId', $representative->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()
            ->createQueryBuilder()
            ->delete('CivixCoreBundle:Representative r')
            ->where('r.id = :representativeId')
            ->setParameter('representativeId', $representative->getId())
            ->getQuery()
            ->execute();
    }

    public function getRepresentativeInformation($representativeId = 0, $storageId = 0)
    {
        $storageId = (int) $storageId;
        $representativeId = (int) $representativeId;

        if (0 < $representativeId) {
            $info = $this->createQueryBuilder('r')
                ->where('r.id = :id')
                ->setParameter('id', $representativeId)
                ->getQuery()
                ->getOneOrNullResult();
        } elseif (0 < $storageId) {
            $info = $this->createQueryBuilder('r')
                ->where('r.storageId = :id')
                ->setParameter('id', $storageId)
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
            ->andWhere($qb->expr()->orX(
                $qb->expr()->like('r.officialTitle', $qb->expr()->literal('%'.$query.'%')),
                $qb->expr()->like('r.firstName', $qb->expr()->literal('%'.$query.'%')),
                $qb->expr()->like('r.lastName', $qb->expr()->literal('%'.$query.'%'))
            ))
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

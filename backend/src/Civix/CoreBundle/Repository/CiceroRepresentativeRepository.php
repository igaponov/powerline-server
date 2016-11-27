<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class CiceroRepresentativeRepository extends EntityRepository
{
    public function getByOfficialInfo($firstname, $lastname, $title)
    {
        return $this->createQueryBuilder('r')
                ->where('r.firstName = :firstname')
                ->andWhere('r.lastName = :lastname')
                ->andWhere('r.officialTitle = :title')
                ->setParameter('firstname', $firstname)
                ->setParameter('lastname', $lastname)
                ->setParameter('title', $title)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function getSTRepresentativeListByUser($districts)
    {
        return $this->getEntityManager()->createQueryBuilder()
               ->select('reprSt, repr')
               ->from('CivixCoreBundle:RepresentativeStorage', 'reprSt')
               ->leftJoin('reprSt.representative', 'repr')
               ->innerJoin('reprSt.district', 'distr')
               ->where('reprSt.district in (:ids)')
               ->setParameter('ids', $districts)
               ->orderBy('distr.districtType')
               ->getQuery()
               ->getResult();
    }

    public function purgeRepresentativeStorage()
    {
        return $this->createQueryBuilder('repr')
                ->delete()->getQuery()->execute();
    }

    public function getSTRepresenativeWithoutLink()
    {
        return $this->createQueryBuilder('repr')
            ->where('repr.openstateId IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findSTRepresentativeByUpdatedAt($maxDateAt, $limit)
    {
        return $this->createQueryBuilder('repr')
            ->where('repr.updatedAt <= :curdate')
            ->setParameter('curdate', $maxDateAt)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findSTRepresentativeByState($state)
    {
        return $this->createQueryBuilder('repr')
            ->where('repr.state = :state')
            ->setParameter('state', $state)
            ->getQuery()
            ->getResult();
    }
}

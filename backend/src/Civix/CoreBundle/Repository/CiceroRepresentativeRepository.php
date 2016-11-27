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

    public function getByDistricts($districts)
    {
        return $this->createQueryBuilder('r')
               ->where($this->_em->getExpressionBuilder()->in('r.district', $districts))
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

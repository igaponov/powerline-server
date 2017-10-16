<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Representative;
use Doctrine\ORM\EntityRepository;

class RepresentativeRepository extends EntityRepository
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

    /**
     * @param $districts
     * @return Representative[]
     */
    public function getByDistricts($districts)
    {
        if (!$districts) {
            return [];
        }

        return $this->createQueryBuilder('r')
                ->innerJoin('r.district', 'd')
               ->where($this->_em->getExpressionBuilder()->in('r.district', $districts))
                ->orderBy('d.districtType')
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

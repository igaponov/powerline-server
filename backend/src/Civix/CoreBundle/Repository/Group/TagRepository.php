<?php

namespace Civix\CoreBundle\Repository\Group;

use Civix\CoreBundle\Entity\Group\Tag;
use Doctrine\ORM\EntityRepository;

class TagRepository extends EntityRepository
{
    /**
     * @param string $name
     * @param int $limit
     * @return array|Tag[]
     */
    public function findByName(string $name, int $limit): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.name LIKE :name')
            ->setParameter(':name', trim($name, '%').'%')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
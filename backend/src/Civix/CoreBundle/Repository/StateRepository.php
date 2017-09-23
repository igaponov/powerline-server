<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class StateRepository extends EntityRepository
{
    public function getStatesWithRepresentative()
    {
        return $this->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->select('st.code, COUNT(rs.id) AS stcount, MIN(rs.updated_at) as lastUpdatedAt')
            ->from('states', 'st')
            ->leftJoin('st', 'cicero_representatives', 'rs', 'rs.state = st.code')
            ->groupBy('st.code')
            ->orderBy('lastUpdatedAt', 'DESC')
            ->addOrderBy('st.code', 'ASC');
    }
}

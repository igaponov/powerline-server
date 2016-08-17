<?php

namespace Civix\CoreBundle\Repository\UserPetition;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityRepository;

class SignatureRepository extends EntityRepository
{
    public function calcVoices(UserPetition $petition)
    {
        $calcResult = $this->createQueryBuilder('s')
            ->select('s.option, count(s.option) as voice_count')
            ->where('s.petition = :petition')
            ->groupBy('s.option')
            ->setParameter('petition', $petition)
            ->getQuery()
            ->getResult();

        $voicesByOptionId = array();
        foreach ($calcResult as $voicesRow) {
            $voicesByOptionId[$voicesRow['option']] = $voicesRow['voice_count'];
        }

        return $voicesByOptionId;
    }

    public function getCountSignatureFromGroup(UserPetition $petition)
    {
        return (int)$this->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->innerJoin('s.petition', 'p')
            ->innerJoin('p.group', 'gr')
            ->innerJoin('gr.users', 'ug')
            ->innerJoin('ug.user', 'u', 'WITH', 'u.id = s.user')
            ->where('s.petition = :petition')
            ->setParameter('petition', $petition)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @param array $criteria
     * @return \Doctrine\ORM\Query
     */
    public function getFindByUserAndCriteriaQuery(User $user, $criteria)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user);

        if (!empty($criteria['start'])) {
            $qb->andWhere('s.createdAt > :start')
                ->setParameter(':start', $criteria['start']);
        }
        
        return $qb->getQuery();
    }
}

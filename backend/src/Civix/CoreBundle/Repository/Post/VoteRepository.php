<?php

namespace Civix\CoreBundle\Repository\Post;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Post\Vote;
use Doctrine\ORM\EntityRepository;

class VoteRepository extends EntityRepository
{
    public function calcVoices(Post $petition)
    {
        $calcResult = $this->createQueryBuilder('v')
            ->select('v.option, count(v.option) as voice_count')
            ->where('v.petition = :petition')
            ->groupBy('v.option')
            ->setParameter('petition', $petition)
            ->getQuery()
            ->getResult();

        $voicesByOptionId = array();
        foreach ($calcResult as $voicesRow) {
            $voicesByOptionId[$voicesRow['option']] = $voicesRow['voice_count'];
        }

        return $voicesByOptionId;
    }

    public function getCountVoterFromGroup(Post $post)
    {
        return (int)$this->createQueryBuilder('v')
            ->select('COUNT(v)')
            ->innerJoin('v.petition', 'p')
            ->innerJoin('p.group', 'gr')
            ->innerJoin('gr.users', 'ug')
            ->innerJoin('ug.user', 'u', 'WITH', 'u.id = v.user')
            ->where('v.petition = :petition')
            ->setParameter('petition', $post)
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
        $qb = $this->createQueryBuilder('v')
            ->where('v.user = :user')
            ->setParameter('user', $user);

        if (!empty($criteria['start'])) {
            $qb->andWhere('v.createdAt > :start')
                ->setParameter(':start', $criteria['start']);
        }
        
        return $qb->getQuery();
    }
}

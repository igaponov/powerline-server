<?php

namespace Civix\CoreBundle\Repository\Poll;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Poll\Answer;

class AnswerRepository extends EntityRepository
{
    public function getAnswersByQuestion($question)
    {
        return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('a')
                ->from('CivixCoreBundle:Poll\Answer', 'a')
                ->join('a.user', 'u')
                ->where('a.question  = :question')
                ->setParameter('question', $question)
                ->getQuery();
    }

    public function getAnswersByInfluence(\Civix\CoreBundle\Entity\User $follower, Question $question)
    {
        return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('a, u')
                ->from('CivixCoreBundle:Poll\Answer', 'a')
                ->leftJoin('a.user', 'u')
                ->leftJoin('CivixCoreBundle:UserFollow', 'uf', 'WITH', 'a.user = uf.user')
                ->where('a.question  = :question')
                ->andWhere('uf.status  = :status')
                ->andWhere('uf.follower  = :follower')
                ->setParameter('question', $question)
                ->setParameter('status', \Civix\CoreBundle\Entity\UserFollow::STATUS_ACTIVE)
                ->setParameter('follower', $follower)
                ->getQuery()
                ->getResult();
    }

    public function getAnswersByNotInfluence(User $follower, Question $question, $maxResults = 5)
    {
        return $this->getEntityManager()
                ->createQuery('SELECT a FROM CivixCoreBundle:Poll\Answer a 
                    WHERE a.user NOT IN(SELECT IDENTITY(uf.user) 
                    FROM  CivixCoreBundle:UserFollow uf 
                    WHERE uf.follower = :follower
                    AND uf.status  = :status)
                    AND a.user <> :followerId
                    AND a.question = :question')
                ->setParameter('question', $question)
                ->setParameter('follower', $follower)
                ->setParameter('status', \Civix\CoreBundle\Entity\UserFollow::STATUS_ACTIVE)
                ->setMaxResults($maxResults)
                ->getResult();
    }

    public function findSignedUsersByPetition(Petition $petition)
    {
        $answers = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('a, u')
            ->from(Answer::class, 'a')
            ->leftJoin('a.user', 'u')
            ->where('a.question = :petition')
            ->setParameter('petition', $petition)
            ->getQuery()
            ->getResult()
        ;

        return array_map(function (Answer $answer) {
            return $answer->getUser();
        }, $answers);
    }

    /**
     * @param User $user
     * @param array $criteria
     * @return \Doctrine\ORM\Query
     */
    public function getFindByUserAndCriteriaQuery(User $user, $criteria)
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user);

        if (!empty($criteria['start'])) {
            $qb->andWhere('a.createdAt > :start')
                ->setParameter(':start', $criteria['start']);
        }

        return $qb->getQuery();
    }
}

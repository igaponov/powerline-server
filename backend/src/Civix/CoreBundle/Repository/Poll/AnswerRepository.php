<?php

namespace Civix\CoreBundle\Repository\Poll;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\UserFollow;
use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Poll\Answer;

class AnswerRepository extends EntityRepository
{
    public function getAnswersByQuestion(Question $question, User $user = null, $revert = false)
    {
        $qb = $this->createQueryBuilder('a')
                ->join('a.user', 'u')
                ->where('a.question  = :question')
                ->setParameter('question', $question);

        if ($user) {
            $qb->leftJoin('u.followers', 'uf', 'WITH', 'uf.follower = :follower AND uf.status = :status')
                ->andWhere($revert ? 'uf.id IS NULL' : 'uf.id IS NOT NULL')
                ->setParameter('status', UserFollow::STATUS_ACTIVE)
                ->setParameter('follower', $user);
        }

        return $qb->getQuery();
    }

    public function getAnswersByInfluence(User $follower, Question $question)
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
                ->setParameter('status', UserFollow::STATUS_ACTIVE)
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
                    AND a.user <> :follower
                    AND a.question = :question')
                ->setParameter('question', $question)
                ->setParameter('follower', $follower)
                ->setParameter('status', UserFollow::STATUS_ACTIVE)
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

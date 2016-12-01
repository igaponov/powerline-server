<?php

namespace Civix\CoreBundle\Repository\Poll;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Security\Core\User\UserInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Answer;

class QuestionRepository extends EntityRepository
{
    /**
     * Find question by ID considering seeking user.
     *
     * @param int  $id
     * @param User $user
     *
     * @return array
     */
    public function findAsUser($id, $user)
    {
        return $this->findOneWithUserAnswerAndGroups(compact('id', 'user'));
    }

    public function findOneWithUserAnswerAndGroups($criteria)
    {
        $questions = $this->createQueryBuilder('p')
            ->select('p, a, g1, g2, g3')
            ->leftJoin('p.answers', 'a', 'WITH', 'a.user = :user')
            ->leftJoin('p.group', 'g1')
            ->leftJoin('g1.parent', 'g2')
            ->leftJoin('g2.parent', 'g3')
            ->where('p.id = :id')
            ->setParameter('id', $criteria['id'])
            ->setParameter('user', $criteria['user'])
            ->getQuery()
            ->getOneOrNullResult();

        return $questions;
    }

    public function getQuestionWithAnswers($id)
    {
        return $this->_em
            ->createQueryBuilder()
            ->select('q, a, o, oa')
            ->from('CivixCoreBundle:Poll\Question', 'q')
            ->leftJoin('q.answers', 'a')
            ->leftJoin('q.options', 'o')
            ->leftJoin('o.answers', 'oa')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult()
        ;
    }

    public function getIncomingAnswersByRepr($repr)
    {
        return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('q')
                ->from('CivixCoreBundle:Poll\Question', 'q')
                ->leftJoin('q.recipients', 'repr')
                ->where('q.publishedAt <= :publishDate')
                ->andWhere('repr.id = :represantativeId')
                ->orderBy('q.publishedAt', 'DESC')
                ->setParameter('publishDate', new \DateTime('now - 1 day'))
                ->setParameter('represantativeId', $repr->getId())
                ->getQuery();
    }

    public function getCountPerMonthQuestionByOwner($owner, $type)
    {
        $currentDate = new \DateTime();
        $resetTimeDate = new \DateTime($currentDate->format('Y-m-d'));
        $startOfMonth = $resetTimeDate->modify('first day of this month');

        $questionCount = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('count(q) as questionCount')
                ->from('CivixCoreBundle:Poll\Question\\'.$type, 'q')
                ->where('q.user = :user')
                ->andWhere('q.publishedAt >= :startOfMonth')
                ->andWhere('q.publishedAt <= :endOfMonth')
                ->setParameter('user', $owner)
                ->setParameter('startOfMonth', $startOfMonth)
                ->setParameter('endOfMonth', $currentDate)
                ->getQuery()
                ->getOneOrNullResult();

        return isset($questionCount['questionCount']) ? (int) $questionCount['questionCount'] : 0;
    }

    public function getPublishedLeaderNewsQuery(UserInterface $user)
    {
        $className = ucfirst($user->getType()).'News';

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ln')
            ->from("CivixCoreBundle:Poll\\Question\\{$className}", 'ln')
            ->where('ln.publishedAt IS NOT NULL')
            ->andWhere('ln.user = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('ln.publishedAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getNewLeaderNewsQuery(UserInterface $user)
    {
        $className = ucfirst($user->getType()).'News';

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ln')
            ->from("CivixCoreBundle:Poll\\Question\\{$className}", 'ln')
            ->where('ln.publishedAt IS NULL')
            ->andWhere('ln.user = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('ln.createdAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getPublishedPetitionsQuery(UserInterface $user)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p, count(u.email) as countEmails')
            ->from($this->getPetitionRepositoryName($user), 'p')
            ->leftJoin('p.answers', 'a', Join::WITH, 'a.privacy = :privacy')
            ->leftJoin('a.user', 'u')
            ->where('p.publishedAt IS NOT NULL')
            ->andWhere('p.user = :userId')
            ->setParameter('userId', $user->getId())
            ->setParameter('privacy', Answer::PRIVACY_PUBLIC)
            ->orderBy('p.publishedAt', 'DESC')
            ->groupBy('p')
            ->getQuery()
            ;
    }

    public function getNewPetitionsQuery(UserInterface $user)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from($this->getPetitionRepositoryName($user), 'p')
            ->where('p.publishedAt IS NULL')
            ->andWhere('p.user = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
        ;
    }

    public function updateAnswersCount(Question $question)
    {
        $count = $this->getEntityManager()
            ->createQuery('SELECT count(a) FROM CivixCoreBundle:Poll\Answer a WHERE a.question = :question')
            ->setParameter('question', $question)
            ->getSingleScalarResult()
        ;
        $question->setAnswersCount($count);
        $this->getEntityManager()->flush($question);
    }

    public function getPublishedQuestionQuery(UserInterface $user, $questionClass)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from($questionClass, 'p')
            ->where('p.publishedAt IS NOT NULL')
            ->andWhere('p.user = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getUnPublishedQuestionQuery(UserInterface $user, $questionClass)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from($questionClass, 'p')
            ->where('p.publishedAt IS NULL')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getSendingOutQuestionQuery(UserInterface $user, $questionClass)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from($questionClass, 'p')
            ->where('p.publishedAt > :date')
            ->andWhere('p.user = :user')
            ->setParameter('date', new \DateTime('now - 1 day'))
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getArchiveQuestionQuery(UserInterface $user, $questionClass)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from($questionClass, 'p')
            ->where('p.publishedAt <= :date')
            ->andWhere('p.user = :user')
            ->setParameter('date', new \DateTime('now - 1 day'))
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
        ;
    }

    public function getFilteredQuestionQuery($filter, LeaderContentRootInterface $root)
    {
        $query = $this->getQuestionQuery($root);
        switch ($filter) {
            case 'published':
                $query->andWhere('p.publishedAt IS NOT NULL');
                break;
            case 'unpublished':
                $query->andWhere('p.publishedAt IS NULL');
                break;
            case 'publishing':
                $query->andWhere('p.publishedAt > :date')
                    ->setParameter('date', new \DateTime('now - 1 day'));
                break;
            case 'archived':
                $query->andWhere('p.publishedAt <= :date')
                    ->setParameter('date', new \DateTime('now - 1 day'));
                break;
        }
        
        return $query;
    }

    /**
     * @param LeaderContentRootInterface $root
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQuestionQuery(LeaderContentRootInterface $root)
    {
        $qb = $this->createQueryBuilder('p')
            ->setParameter(':root', $root)
            ->orderBy('p.createdAt', 'DESC')
        ;
        if ($root instanceof Group) {
            $qb->where('p.group = :root');
        } elseif ($root instanceof Representative) {
            $qb->where('p.representative = :root');
        }

        return $qb;
    }

    public function getPublishedQuestionWithAnswers($id, $questionClass)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('q, a, o, oa')
            ->from($questionClass, 'q')
            ->leftJoin('q.answers', 'a')
            ->leftJoin('q.options', 'o')
            ->leftJoin('o.answers', 'oa')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult()
        ;
    }

    public function getGroupQuestion($id)
    {
        $query = $this->createQueryBuilder('q')
            ->where('q.id = :id')
            ->setParameter(':id', $id)
            ->andWhere('
                q INSTANCE OF (
                    Civix\CoreBundle\Entity\Poll\Question\Group,
                    Civix\CoreBundle\Entity\Poll\Question\GroupEvent,
                    Civix\CoreBundle\Entity\Poll\Question\GroupNews,
                    Civix\CoreBundle\Entity\Poll\Question\GroupPaymentRequest,
                    Civix\CoreBundle\Entity\Poll\Question\GroupPetition
                )')
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param LeaderContentRootInterface $root
     *
     * @return string
     */
    private function getPetitionRepositoryName(LeaderContentRootInterface $root)
    {
        if ($root instanceof Representative) {
            return 'CivixCoreBundle:Poll\Question\RepresentativePetition';
        } elseif ($root instanceof Group) {
            return 'CivixCoreBundle:Poll\Question\GroupPetition';
        } else {
            throw new \RuntimeException(get_class($root).' doesn\'t have a poll repository');
        }
    }
}

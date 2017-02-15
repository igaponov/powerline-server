<?php

namespace Civix\CoreBundle\Repository\Poll;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\UserFollow;
use Doctrine\DBAL\Driver\Statement;
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

    /**
     * @param Question $question
     * @return Statement
     */
    public function getResponsesByQuestion(Question $question)
    {
        $platform = $this->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();
        $fields = $question->getGroup()->getFields();
        $permissions = $question->getGroup()->getRequiredPermissions();
        $qb = $this->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->from('poll_answers', 'a')
            ->leftJoin('a', 'user', 'u', 'a.user_id = u.id')
            ->leftJoin('u', 'users_follow', 'f', 'f.user_id = u.id')
            ->leftJoin('a', 'poll_options', 'o', 'a.option_id = o.id')
            ->where('a.question_id = :poll')
            ->setParameter(':poll', $question->getId())
            ->setParameter(':public', Answer::PRIVACY_PUBLIC)
            ->groupBy('a.id');
        foreach ([$platform->getConcatExpression('firstName', '" "', 'lastName') => 'name', $platform->getConcatExpression('address1', '", "', 'address2') => 'address', 'city', 'state', 'country', 'zip' => 'zip_code', 'email', 'phone'] as $attribute => $alias) {
            if (!in_array('permissions_'.$alias, $permissions)) {
                continue;
            }
            if (!is_string($attribute)) {
                $attribute = $alias;
            }
            if ($alias === 'name') {
                $qb->addSelect("CASE WHEN a.privacy = :public THEN u.{$attribute} ELSE NULL END AS {$alias}");
            } else {
                $qb->addSelect("u.{$attribute} AS {$alias}");
            }
        }
        $qb->addSelect('u.bio, u.slogan, CASE WHEN u.facebook_id IS NOT NULL THEN 1 ELSE 0 END AS facebook, COUNT(f.id) AS followers, 0 AS karma');
        foreach ($fields as $k => $field) {
            $qb->addSelect("v$k.field_value AS {$platform->quoteSingleIdentifier($field->getFieldName())}")
                ->leftJoin('u', 'groups_fields_values', 'v'.$k, "v$k.user_id = u.id AND v$k.field_id = :field$k")
                ->setParameter(":field$k", $field->getId());
        }
        $qb->addSelect('o.value AS choice, a.comment');

        return $qb->execute();
    }

    /**
     * @param Group $group
     * @return Statement
     */
    public function getResponsesByGroup(Group $group)
    {
        $fields = $group->getFields();
        $polls = $this->getEntityManager()
            ->getRepository(Question::class)
            ->findBy(['group' => $group]);
        $qb = $this->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->select(
                'u.firstName AS first_name, u.lastName AS last_name, u.address1, u.address2, u.city, u.state, u.country, u.zip, u.email, u.phone, u.bio, u.slogan, CASE WHEN u.facebook_id IS NOT NULL THEN 1 ELSE 0 END AS facebook, COUNT(f.id) AS followers, 0 AS karma'
                )
            ->from('user', 'u')
            ->leftJoin('u', 'users_follow', 'f', 'f.user_id = u.id')
            ->groupBy('u.id');
        $platform = $this->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();
        foreach ($fields as $k => $field) {
            $qb->addSelect("v$k.field_value AS {$platform->quoteSingleIdentifier($field->getFieldName())}")
                ->leftJoin('u', 'groups_fields_values', 'v'.$k, "v$k.user_id = u.id AND v$k.field_id = :field$k")
                ->setParameter(":field$k", $field->getId());
        }
        foreach ($polls as $k => $poll) {
            $qb->addSelect("CASE WHEN a$k.privacy = :private THEN \"Anonymous\" ELSE o$k.value END AS {$platform->quoteSingleIdentifier($poll->getSubject())}")
                ->leftJoin('u', 'poll_answers', 'a'.$k, "a$k.user_id = u.id AND a$k.question_id = :poll$k")
                ->leftJoin('a'.$k, 'poll_options', 'o'.$k, "a$k.option_id = o$k.id")
                ->setParameter(':poll'.$k, $poll->getId())
                ->setParameter(':private', Answer::PRIVACY_PRIVATE);
        }

        return $qb->execute();
    }
}

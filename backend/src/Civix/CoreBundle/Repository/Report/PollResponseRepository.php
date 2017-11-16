<?php

namespace Civix\CoreBundle\Repository\Report;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class PollResponseRepository extends EntityRepository
{
    public function getPollResponseReport(User $user, Question $poll)
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.user = :user')
            ->setParameter(':user', $user->getId())
            ->andWhere('pr.poll = :poll')
            ->setParameter(':poll', $poll)
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function insertPollResponseReport(Answer $answer)
    {
        $poll = $answer->getQuestion();

        return $this->getEntityManager()->getConnection()
            ->insert('poll_response_report', [
                'user_id' => $answer->getUser()->getId(),
                'poll_id' => $poll->getId(),
                'group_id' => $poll->getGroup()->getId(),
                'text' => (string)($poll instanceof Question\PaymentRequest || $poll instanceof Question\LeaderEvent ? $poll->getTitle() : $poll->getSubject()),
                'answer' => (string)$answer->getOption()->getValue(),
                'comment' => (string)$answer->getComment(),
                'privacy' => (int)$answer->getPrivacy(),
            ]);
    }

    public function updatePollResponseReport(Answer $answer)
    {
        $poll = $answer->getQuestion();

        return $this->getEntityManager()->getConnection()
            ->update('poll_response_report', [
                'group_id' => $poll->getGroup()->getId(),
                'text' => (string)($poll instanceof Question\PaymentRequest || $poll instanceof Question\LeaderEvent ? $poll->getTitle() : $poll->getSubject()),
                'answer' => (string)$answer->getOption()->getValue(),
                'comment' => (string)$answer->getComment(),
                'privacy' => (int)$answer->getPrivacy(),
            ], [
                'user_id' => $answer->getUser()->getId(),
                'poll_id' => $poll->getId(),
            ]);
    }
}
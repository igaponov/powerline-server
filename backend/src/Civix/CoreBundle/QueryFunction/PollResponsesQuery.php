<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Report\PollResponseReport;
use Doctrine\ORM\QueryBuilder;

class PollResponsesQuery extends AbstractUserReportQuery
{
    public function __invoke(Question $question)
    {
        $permissions = $question->getGroup()->getRequiredPermissions();
        $qb = $this->createQueryBuilder($permissions);
        $qb->join(PollResponseReport::class, 'p', 'WITH', 'p.user = u.id')
            ->where('p.poll = :poll')
            ->setParameter(':poll', $question->getId())
            ->groupBy('p.user, p.poll');
        if (array_diff($permissions, ['permissions_responses'])) {
            $qb->setParameter(':public', Answer::PRIVACY_PUBLIC);
        }
        $qb->addSelect('p.text, p.answer, p.comment');

        return $qb->getQuery()->getArrayResult();
    }

    public function processAttribute($alias, $attribute, QueryBuilder $qb)
    {
        if ($alias === 'name') {
            $qb->addSelect("CASE WHEN p.privacy = :public THEN {$attribute} ELSE '' END AS {$alias}");
        } else {
            parent::processAttribute($alias, $attribute, $qb);
        }
    }
}
<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Report\PollResponseReport;

class GroupResponsesQuery extends AbstractUserReportQuery
{
    public function __invoke(Group $group) : array
    {
        $permissions = $group->getRequiredPermissions();
        $qb = $this->createQueryBuilder($permissions);
        $qb->join(PollResponseReport::class, 'p', 'WITH', 'p.user = u.id')
            ->where('p.group = :group')
            ->setParameter(':group', $group->getId())
            ->groupBy('u.id')
            ->addSelect("CONCAT('{', GROUP_CONCAT(CONCAT('\"', p.text, '\": \"', CASE WHEN p.privacy = :private THEN 'Anonymous' ELSE p.answer END, '\"')), '}') AS polls")
            ->setParameter(':private', Answer::PRIVACY_PRIVATE);
        $result = $qb->getQuery()->getArrayResult();
        foreach ($result as &$item) {
            $item['polls'] = json_decode($item['polls'], true);
        }

        return $result;
    }
}
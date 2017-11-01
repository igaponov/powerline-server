<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManagerInterface;

final class UserGroupsQuery
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(User $user)
    {
        $qb = $this->em->createQueryBuilder();

        return $qb
            ->select('ug, g, gm, a, COUNT(u) AS totalMembers, CASE WHEN g.owner = :user THEN 0 WHEN gm.user IS NOT NULL THEN 1 ELSE 2 END AS HIDDEN sortCondition')
            ->from(UserGroup::class, 'ug')
            ->leftJoin('ug.group', 'g')
            ->leftJoin('g.advancedAttributes', 'a')
            ->leftJoin('g.users', 'u')
            ->leftJoin('g.managers', 'gm', 'WITH', 'gm.user = :user')
            ->where('ug.user = :user')
            ->setParameter(':user', $user)
            ->orderBy('sortCondition', 'ASC')
            ->addOrderBy('g.officialName', 'ASC')
            ->groupBy('ug.id')
            ->getQuery();
    }

    public function runPostQueries(User $user, UserGroup ...$userGroups): void
    {
        $builder = new ActivitiesQueryBuilder($this->em);
        $cases = $builder->getTypeCases();
        $query = $this->em->createQueryBuilder()
            ->select('act.id', 'IDENTITY(act.group) AS grp', 'COUNT(act.id) AS cnt')
            ->from(Activity::class, 'act')
            ->leftJoin('act.activityRead', 'act_r', 'WITH', 'act_r.user = :user')
            ->leftJoin('act.question', 'q')
            ->leftJoin('q.answers', 'qa', 'WITH', 'qa.user = :user')
            ->leftJoin('act.post', 'p')
            ->leftJoin('p.votes', 'pv', 'WITH', 'pv.user = :user')
            ->leftJoin('act.petition', 'up')
            ->leftJoin('up.signatures', 'ups', 'WITH', 'ups.user = :user')
            ->setParameter(':user', $user)
            ->where('act.group IN (:groups)')
            ->andWhere(implode(' OR ', $cases))
            ->setParameter(':groups', array_map(function (UserGroup $userGroup) {
                return $userGroup->getGroup();
            }, $userGroups))
            ->andWhere('act.sentAt > :startAt')
            ->setParameter('startAt', (new \DateTime('-30 days'))->format('Y-m-d H:i:s'))
            ->groupBy('act.group')
            ->getQuery();
        $results = [];
        foreach ($query->getResult() as $item) {
            $results[$item['grp']] = (int)$item['cnt'];
        }
        foreach ($userGroups as $userGroup) {
            $id = $userGroup->getGroup()->getId();
            if (isset($results[$id])) {
                $userGroup->getGroup()->setPriorityItemCount($results[$id]);
            }
        }
    }
}
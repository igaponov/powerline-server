<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;

final class ActivitiesQuery
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getFilterByGroup($group): \Closure
    {
        return function (QueryBuilder $qb) use ($group) {
            if ($group) {
                $qb->andWhere('g.id = :group')
                    ->setParameter(':group', $group);
            }
        };
    }

    public static function getFilterByStartAt(?\DateTime $startAt): \Closure
    {
        return function (QueryBuilder $qb) use ($startAt) {
            if ($startAt) {
                $qb->andWhere('act.sentAt > :startAt')
                    ->setParameter('startAt', $startAt->format('Y-m-d H:i:s'));
            }
        };
    }

    public static function getFilterByFollowing(User $following): \Closure
    {
        return function (QueryBuilder $qb) use ($following) {
            $qb->andWhere('act.user = :following')
                ->setParameter(':following', $following);
        };
    }

    public static function getFilterByUser(): \Closure
    {
        return function (QueryBuilder $qb) {
            $qb->andWhere('act.user = :user');
        };
    }

    public static function getFilterByFollowed(User $user): \Closure
    {
        return function (QueryBuilder $qb) use ($user) {
            $qb->andWhere('act.user IN (:followings)')
                ->setParameter(':followings', $user->getFollowingIds());
        };
    }

    public static function getFilterByNonFollowed(User $user): \Closure
    {
        return function (QueryBuilder $qb) use ($user) {
            $qb->andWhere('act.user NOT IN (:followings)')
                ->setParameter(':followings', $user->getFollowingIds());
        };
    }

    public function __invoke(User $user, ?array $types, array $filters): Query
    {
        $builder = new ActivitiesQueryBuilder($this->em);
        $cases = $builder->getTypeCases();
        if ($types) {
            $cases = array_intersect_key($cases, array_flip($types));
        }

        $qb = $builder($user, $types)
            ->select('act', 'act_r', 'g', 'u')
            // 0 = Prioritized Zone (unread, unanswered)
            // 2 = Expired Zone (expired)
            // 1 = Non-Prioritized Zone (others)
            ->addSelect('
            (CASE WHEN act.expireAt < CURRENT_TIMESTAMP()
            THEN 2 
            WHEN 
                '.($cases ? implode(' OR ', $cases) : 'FALSE').'
            THEN 0
            ELSE 1
            END) AS zone')
            ->addSelect('CASE WHEN act_c.user = :user THEN 0 ELSE 1 END AS HIDDEN is_followed')
            ->orderBy('zone', 'ASC') // order by priority zone
            ->addOrderBy('is_followed', 'ASC') // order by followed user
            ->addOrderBy('act.sentAt', 'DESC');

        if (isset($cases['poll'])) {
            $qb->addSelect('q', 'qa', 'qs');
        }
        if (isset($cases['post'])) {
            $qb->addSelect('p', 'pv', 'pos');
        }
        if (isset($cases['petition'])) {
            $qb->addSelect('up', 'ups', 'ps');
        }

        array_walk($filters, function (callable $filter) use ($qb) {
            $filter($qb);
        });

        $query = $qb->getQuery();
        /** @noinspection PhpDeprecationInspection */
        $query->setHint(QuerySubscriber::HINT_COUNT, $this->getCount($qb));

        return $query;
    }

    /**
     * @param $originalQueryBuilder
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCount(QueryBuilder $originalQueryBuilder): int
    {
        $qb = clone $originalQueryBuilder;
        $qb->distinct(false)
            ->select('COUNT(DISTINCT act)')
            ->resetDQLPart('orderBy')
            ->setParameters(clone $originalQueryBuilder->getParameters());

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
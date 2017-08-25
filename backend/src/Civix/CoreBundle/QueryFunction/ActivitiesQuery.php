<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;

final class ActivitiesQuery
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

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

    public static function getFilterByPostId($id): \Closure
    {
        return function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('act.post = :post')
                ->setParameter(':post', $id);
        };
    }

    public static function getFilterByPollId($id): \Closure
    {
        return function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('act.question = :poll')
                ->setParameter(':poll', $id);
        };
    }

    public static function getFilterByPetitionId($id): \Closure
    {
        return function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('act.petition = :petition')
                ->setParameter(':petition', $id);
        };
    }

    public function __invoke(User $user, ?array $types, array $filters, bool $addPublicGroups = false): Query
    {
        $builder = new ActivitiesQueryBuilder($this->em);
        $cases = $builder->getTypeCases();
        if ($types) {
            $cases = array_intersect_key($cases, array_flip($types));
        }

        $qb = $builder($user, $types, $addPublicGroups)
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

    public function runPostQueries(array $activities): void
    {
        $postIds = $pollIds = $petitionIds = [];
        /** @var Activity[] $activities */
        foreach ($activities as $activity) {
            if ($activity->getPost()) {
                $postIds[] = $activity->getPost()->getId();
            }
            if ($activity->getPetition()) {
                $petitionIds[] = $activity->getPetition()->getId();
            }
            if ($activity->getQuestion()) {
                $pollIds[] = $activity->getQuestion()->getId();
            }
        }
        if ($postIds) {
            $this->selectComments(Post::class, $postIds);
        }
        if ($petitionIds) {
            $this->selectComments(UserPetition::class, $petitionIds);
        }
        if ($pollIds) {
            $this->selectComments(Question::class, $pollIds);
            $this->selectEducationalContexts($pollIds);
        }
    }

    /**
     * @param $originalQueryBuilder
     * @return int
     * @throws \Doctrine\ORM\NoResultException
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

    /**
     * Select entity's comments by multi-step hydration
     *
     * @param string $class
     * @param array $ids
     */
    private function selectComments(string $class, array $ids): void
    {
        $this->em->createQueryBuilder()
            ->select('PARTIAL e.{id}', 'c', 'u')
            ->from($class, 'e')
            ->leftJoin('e.comments', 'c')
            ->leftJoin('c.user', 'u')
            ->where('e.id IN (:ids)')
            ->setParameter(':ids', $ids)
            ->orderBy('c.id')
            ->groupBy('e.id')
            ->getQuery()->execute();
    }

    /**
     * Select entity's educational contexts by multi-step hydration
     *
     * @param array $ids
     */
    private function selectEducationalContexts(array $ids): void
    {
        $this->em->createQueryBuilder()
            ->select('PARTIAL p.{id}', 'c')
            ->from(Question::class, 'p')
            ->leftJoin('p.educationalContext', 'c')
            ->where('p.id IN (:ids)')
            ->setParameter(':ids', $ids)
            ->getQuery()->execute();
    }
}
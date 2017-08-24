<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

final class ActivitiesQueryBuilder
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(User $user, ?array $types, bool $addPublicGroups = false): QueryBuilder
    {
        $expr = $this->em->getExpressionBuilder();

        $districtIds = $user->getDistrictsIds();
        $groupSectionIds = $user->getGroupSectionsIds();
        $groupIds = $this->em->getRepository(UserGroup::class)->getActiveGroupIds($user);

        $conditions = [
            $expr->in('act_c.district', ':districtIds'),
            $expr->in('act_c.group', ':groupIds'),
            $expr->in('act_c.groupSection', ':groupSectionIds')
        ];
        if ($addPublicGroups) {
            $conditions[] = $expr->in('g.groupType', Group::getLocalTypes());
        }
        $qb = $this->em->getRepository(Activity::class)->createQueryBuilder('act')
            ->distinct(true)
            ->leftJoin('act.user', 'u')
            ->leftJoin('act.activityConditions', 'act_c')
            ->leftJoin('act.activityRead', 'act_r', Query\Expr\Join::WITH, 'act_r.user = :user')
            ->setParameter(':user', $user)
            ->leftJoin('act.group', 'g')
            ->andWhere($expr->orX(...$conditions))
            ->setParameter('districtIds', $districtIds)
            ->setParameter('groupIds', $groupIds)
            ->setParameter('groupSectionIds', $groupSectionIds);
;
        $cases = $this->getTypeCases();
        if ($types) {
            $cases = array_intersect_key($cases, array_flip($types));
        }
        if (isset($cases['poll'])) {
            $qb->leftJoin('act.question', 'q')
                ->leftJoin('q.answers', 'qa', Query\Expr\Join::WITH, 'qa.user = :user')
                ->leftJoin('q.subscribers', 'qs', Query\Expr\Join::WITH, 'qs = :user');
        } else {
            $qb->andWhere('
                act NOT INSTANCE OF (
                    Civix\CoreBundle\Entity\Activities\CrowdfundingPaymentRequest,
                    Civix\CoreBundle\Entity\Activities\LeaderEvent,
                    Civix\CoreBundle\Entity\Activities\LeaderNews,
                    Civix\CoreBundle\Entity\Activities\PaymentRequest,
                    Civix\CoreBundle\Entity\Activities\Petition,
                    Civix\CoreBundle\Entity\Activities\Question
                )
            ');
        }
        if (isset($cases['post'])) {
            $qb->leftJoin('act.post', 'p')
                ->leftJoin('p.votes', 'pv', Query\Expr\Join::WITH, 'pv.user = :user')
                ->leftJoin('p.subscribers', 'pos', Query\Expr\Join::WITH, 'pos = :user');
        } else {
            $qb->andWhere('
                act NOT INSTANCE OF (
                    Civix\CoreBundle\Entity\Activities\Post
                )
            ');
        }
        if (isset($cases['petition'])) {
            $qb->leftJoin('act.petition', 'up')
                ->leftJoin('up.signatures', 'ups', Query\Expr\Join::WITH, 'ups.user = :user')
                ->leftJoin('up.subscribers', 'ps', Query\Expr\Join::WITH, 'ps = :user');
        } else {
            $qb->andWhere('
                act NOT INSTANCE OF (
                    Civix\CoreBundle\Entity\Activities\UserPetition
                )
            ');
        }

        return $qb;
    }

    /**
     * @return array
     */
    public function getTypeCases(): array
    {
        return [
            'poll' => '
                (
                    qa.id IS NULL AND 
                    act NOT INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\LeaderNews, 
                        Civix\CoreBundle\Entity\Activities\Petition,
                        Civix\CoreBundle\Entity\Activities\Post,
                        Civix\CoreBundle\Entity\Activities\UserPetition
                    )
                )
                OR 
                (
                    qa.id IS NULL AND
                    act_r.id IS NULL AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\Petition
                    )
                )
                OR 
                (
                    act_r.id IS NULL AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\LeaderNews
                    )
                )
            ',
            'post' => '
                (
                    p.boosted = true AND
                    pv.id IS NULL AND 
                    (p.user != :user OR act_r.id IS NULL) AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\Post
                    )
                )
            ',
            'petition' => '
                (
                    up.boosted = true AND
                    act_r.id IS NULL AND 
                    ups.id IS NULL AND 
                    (up.user != :user OR act_r.id IS NULL) AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\UserPetition
                    )
                )
            '
        ];
    }
}
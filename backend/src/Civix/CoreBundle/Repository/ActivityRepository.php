<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\Poll\Question;

class ActivityRepository extends EntityRepository
{
    public function findActivities(\DateTime $start, User $user, $closed = false)
    {
        /** @var $em EntityManager */
        $em = $this->getEntityManager();

        /** @var $qb QueryBuilder */
        $qb = $em->createQueryBuilder();
        $expr = $qb->expr();

        $districtsIds = $user->getDistrictsIds();
        $sectionsIds = $user->getGroupSectionsIds();
        $activeGroups = $this->getEntityManager()->getRepository('CivixCoreBundle:UserGroup')->getActiveGroupIds($user);

        $userFollowingIds = $user->getFollowingIds();

        return $qb->select('act')
            ->from('CivixCoreBundle:Activity', 'act')
            ->leftJoin('act.activityConditions', 'act_c')
            ->where($expr->gt('act.sentAt', ':start'))
            ->andWhere(
                $expr->orX(
                    $expr->in('act_c.district', ':userDistrictsIds'),
                    'act_c.isSuperuser = 1',
                    $expr->in('act_c.group', ':userGroupsIds'),
                    $expr->in('act_c.user', ':userFollowingIds'),
                    $expr->in('act_c.groupSection', ':userGroupSectionIds'),
                    ':user MEMBER OF act_c.users'
                )
            )
            ->andWhere($closed ? 'act.expireAt < :now' :
                'act.expireAt > :now OR act INSTANCE OF CivixCoreBundle:Activities\Petition
                OR act INSTANCE OF CivixCoreBundle:Activities\PaymentRequest')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('userDistrictsIds', empty($districtsIds) ? false : $districtsIds)
            ->setParameter('userGroupsIds', empty($activeGroups) ? false : $activeGroups)
            ->setParameter('userFollowingIds', empty($userFollowingIds) ? false : $userFollowingIds)
            ->setParameter('userGroupSectionIds', empty($sectionsIds) ? false : $sectionsIds)
            ->setParameter('now', new \DateTime())
            ->setParameter('user', $user)
            ->orderBy('act.sentAt', 'DESC')
            ->setMaxResults(500)
            ->getQuery()->getResult();
    }

    /**
     * Find activities by user.
     *
     * @param User      $user
     * @param \DateTime $start
     * @param $offset
     * @param $limit
     *
     * @return array
     *
     * @deprecated
     */
    public function findActivitiesByUser(User $user, \DateTime $start, $offset, $limit)
    {
        /** @var $em EntityManager */
        $em = $this->getEntityManager();
 
        /** @var $qb QueryBuilder */
        $qb = $em->createQueryBuilder();
        $expr = $qb->expr();

        $districtsIds = $user->getDistrictsIds();
        $sectionsIds = $user->getGroupSectionsIds();
        $activeGroups = $this->getEntityManager()->getRepository('CivixCoreBundle:UserGroup')
            ->getActiveGroupIds($user);

        $userFollowingIds = $user->getFollowingIds();

        $activities = $qb->select('act')
            // 0 = Prioritized Zone (unread, unanswered)
            // 2 = Expired Zone (expired)
            // 1 = Non-Prioritized Zone (others)
            ->addSelect('
            (CASE WHEN act.expireAt < CURRENT_TIMESTAMP()
            THEN 2 
            WHEN 
                (qa.id IS NULL AND ups.id IS NULL AND pv.id IS NULL AND act NOT INSTANCE OF (
                    Civix\CoreBundle\Entity\Activities\LeaderNews, 
                    Civix\CoreBundle\Entity\Activities\Petition
                ))
                OR 
                (act_r.id IS NULL AND act INSTANCE OF (
                    Civix\CoreBundle\Entity\Activities\LeaderNews, 
                    Civix\CoreBundle\Entity\Activities\Petition
                ))
            THEN 0
            ELSE 1
            END) AS zone')
            ->from('CivixCoreBundle:Activity', 'act')
            ->leftJoin('act.activityConditions', 'act_c')
            ->leftJoin('act.activityRead', 'act_r', Query\Expr\Join::WITH, 'act_r.user = :user')
            ->leftJoin('act.question', 'q')
            ->leftJoin('act.petition', 'up')
            ->leftJoin('act.post', 'p')
            ->leftJoin('q.answers', 'qa')
            ->leftJoin('up.signatures', 'ups')
            ->leftJoin('p.votes', 'pv')
            ->where($expr->gt('act.sentAt', ':start'))
            ->andWhere(
                $expr->orX(
                    $expr->in('act_c.district', ':userDistrictsIds'),
                    'act_c.isSuperuser = 1',
                    $expr->in('act_c.group', ':userGroupsIds'),
                    $expr->in('act_c.user', ':userFollowingIds'),
                    $expr->in('act_c.groupSection', ':userGroupSectionIds'),
                    ':user MEMBER OF act_c.users'
                )
            )
            ->setParameter('userDistrictsIds', empty($districtsIds) ? false : $districtsIds)
            ->setParameter('userGroupsIds', empty($activeGroups) ? false : $activeGroups)
            ->setParameter('userFollowingIds', empty($userFollowingIds) ? false : $userFollowingIds)
            ->setParameter('userGroupSectionIds', empty($sectionsIds) ? false : $sectionsIds)
            ->setParameter('user', $user)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->orderBy('zone', 'ASC') // order by priority zone
            ->addOrderBy('act.sentAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->groupBy('act.id')
            ->getQuery()->getResult();

        $filter = function (ActivityRead $activityRead) use ($user) {
            return $activityRead->getUser()->getId() == $user->getId();
        };
        foreach ($activities as &$activity) {
            $zone = $activity['zone'];
            $activity = reset($activity);
            /** @var Activity $activity */
            $activity->setZone($zone);
            if ($activity->getActivityRead()->filter($filter)->count()) {
                $activity->setRead(true);
            }
        }
        
        return $activities;
    }

    /**
     * Return the count of activities by user.
     *
     * Not Implemented Yet
     *
     * @param User      $user
     * @param \DateTime $start
     *
     * @return array
     */
    public function findActivitiesByUserCount(User $user, \DateTime $start)
    {
        /** @var $em EntityManager */
        $em = $this->getEntityManager();

        /** @var $qb QueryBuilder */
        $qb = $em->createQueryBuilder();
        $expr = $qb->expr();

        $districtsIds = $user->getDistrictsIds();
        $sectionsIds = $user->getGroupSectionsIds();
        $activeGroups = $this->getEntityManager()->getRepository('CivixCoreBundle:UserGroup')
            ->getActiveGroupIds($user);

        $userFollowingIds = $user->getFollowingIds();

        return $qb->select('COUNT(act.id)')
            ->from('CivixCoreBundle:Activity', 'act')
            ->leftJoin('act.activityConditions', 'act_c')
            ->where($expr->gt('act.sentAt', ':start'))
            ->andWhere(
                $expr->orX(
                    $expr->in('act_c.district', ':userDistrictsIds'),
                    'act_c.isSuperuser = 1',
                    $expr->in('act_c.group', ':userGroupsIds'),
                    $expr->in('act_c.user', ':userFollowingIds'),
                    $expr->in('act_c.groupSection', ':userGroupSectionIds'),
                    ':user MEMBER OF act_c.users'
                )
            )
            ->setParameter('userDistrictsIds', empty($districtsIds) ? false : $districtsIds)
            ->setParameter('userGroupsIds', empty($activeGroups) ? false : $activeGroups)
            ->setParameter('userFollowingIds', empty($userFollowingIds) ? false : $userFollowingIds)
            ->setParameter('userGroupSectionIds', empty($sectionsIds) ? false : $sectionsIds)
            ->setParameter('user', $user)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * return an array of activities that are read.
     *
     * @param User      $user
     * @param \Datetime $start
     * @param array     $activites
     *
     * @return mixed
     */
    public function getReadItems(User $user, \Datetime $start, array $activites)
    {
        $readItems = $this->getEntityManager()->getRepository(ActivityRead::class)
            ->findLastIdsByUser($user, $start);
        foreach ($activities as $activity) {
            if (in_array($activity->getId(), $readItems)) {
                $activity->setRead(true);
            }
        }

        return $activities;
    }

    /**
     * Find activities by Following the user.
     *
     * @param User $following
     * @param $offset
     * @param $limit
     *
     * @return array
     */
    public function findActivitiesByFollowing(User $following, $offset, $limit)
    {
        return $this->createQueryBuilder('act')
            ->leftJoin('act.activityConditions', 'act_c')
            ->where('act_c.user = :following')
            ->setParameter('following', $following->getId())
            ->orderBy('act.sentAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()->getResult()
        ;
    }

    public function updateResponseCountQuestion(Question $question)
    {
        $query = $this->getEntityManager()->getConnection()->executeQuery(
            'UPDATE activities
            SET responses_count = (
                SELECT (
                    (
                        SELECT COUNT(pa.id)
                        FROM poll_questions pq
                        LEFT JOIN poll_answers pa ON pq.id = pa.question_id
                        WHERE pq.id = question_id
                    )
                    +
                    (
                        SELECT COUNT(pc.id)
                        FROM poll_questions pq
                        LEFT JOIN poll_comments pc ON pq.id = pc.question_id
                        WHERE pq.id = question_id AND pc.pid IS NULL
                    )
                )
            )
            WHERE question_id = :question'
        );

        return $query->execute([':question' => $question->getId()]);
    }

    public function updateResponseCountUserPetition(UserPetition $petition)
    {
        $query = $this->getEntityManager()->getConnection()->executeQuery(
            'UPDATE activities 
            SET responses_count = (
                SELECT (
                    (
                        SELECT COUNT(ps.id)
                        FROM user_petitions p
                        LEFT JOIN user_petition_signatures ps ON p.id = ps.petition_id
                        WHERE p.id = petition_id
                    )
                    +
                    (
                        SELECT COUNT(pc.id)
                        FROM user_petitions p
                        LEFT JOIN user_petition_comments pc ON p.id = pc.petition_id
                        WHERE p.id = petition_id AND pc.pid IS NULL
                    )
                )
            )
            WHERE petition_id = :petition'
        );

        return $query->execute([':petition' => $petition->getId()]);
    }

    public function updateResponseCountPost(Post $post)
    {
        $query = $this->getEntityManager()->getConnection()->executeQuery(
            "UPDATE activities
            SET responses_count = (
                SELECT (
                    (
                        SELECT COUNT(pv.id)
                        FROM user_posts p
                        LEFT JOIN post_votes pv ON p.id = pv.post_id
                        WHERE p.id = post_id
                    )
                    +
                    (
                        SELECT COUNT(pc.id)
                        FROM user_posts p
                        LEFT JOIN post_comments pc ON p.id = pc.post_id
                        WHERE p.id = post_id AND pc.pid IS NULL
                    )
                )
            )
            WHERE post_id = :post"
        );

        return $query->execute([':post' => $post->getId()]);
    }

    public function getActivitiesByGroupId($groupId, $maxResults = 500)
    {
        /** @var $entityManager EntityManager */
        $entityManager = $this->getEntityManager();

        /** @var $qb QueryBuilder */
        $qb = $entityManager->createQueryBuilder();

        $activities = $qb->select('act, gr')
            ->from('CivixCoreBundle:Activity', 'act')
            ->leftJoin('act.group', 'gr')
            ->where('gr.id =  :groupId')
            ->setParameter('groupId', (int) $groupId)
            ->orderBy('act.sentAt', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();

        return $activities;
    }

    public function getActivitiesByRepresentativeId($representativeId, $maxResults = 500)
    {
        /** @var $entityManager EntityManager */
        $entityManager = $this->getEntityManager();

        /** @var $qb QueryBuilder */
        $qb = $entityManager->createQueryBuilder();

        $activities = $qb->select('act, repr')
            ->from('CivixCoreBundle:Activities\Question', 'act')
            ->leftJoin('act.representative', 'repr')
            ->where('repr.id =  :representativeId')
            ->setParameter('representativeId', (int) $representativeId)
            ->orderBy('act.sentAt', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();

        return $activities;
    }

    public function updateOwnerUser(User $owner)
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.owner', ':data')
            ->where('a.user = :owner')
            ->setParameter('owner', $owner)
            ->setParameter('data', serialize(Activity::toUserOwnerData($owner)))
            ->getQuery()
            ->execute()
        ;
    }

    public function updateOwnerGroup(Group $owner)
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.owner', ':data')
            ->where('a.group = :owner')
            ->andWhere('a.user IS NULL')
            ->setParameter('owner', $owner)
            ->setParameter('data', serialize(Activity::toGroupOwnerData($owner)))
            ->getQuery()
            ->execute()
        ;
    }

    public function updateOwnerRepresentative(Representative $owner)
    {
    }

    public function updateOwnerAdmin(Superuser $owner)
    {
    }

    /**
     * Find activities by user.
     *
     * @param User $user
     * @param \DateTime $start
     *
     * @param null $activityTypes
     * @return Query
     */
    public function getActivitiesByUserQuery(User $user, \DateTime $start = null, $activityTypes = null) {
        return $this->getActivitiesByUserQueryBuilder($user, $start, $activityTypes)
            ->getQuery();
    }

    public function getActivitiesByUserQueryBuilder(User $user, \DateTime $start = null, $activityTypes = null)
    {
        /** @var $em EntityManager */
        $em = $this->getEntityManager();

        /** @var $qb QueryBuilder */
        $qb = $em->createQueryBuilder();
        $expr = $qb->expr();

        $districtsIds = $user->getDistrictsIds();
        $sectionsIds = $user->getGroupSectionsIds();
        $activeGroups = $this->getEntityManager()->getRepository('CivixCoreBundle:UserGroup')
            ->getActiveGroupIds($user);

        $userFollowingIds = $user->getFollowingIds();

        return $this->getActivitiesQueryBuilder($user, $start, $activityTypes)
            ->andWhere(
                $expr->andX(
                    $expr->in('act.group', ':userGroupsIds'),
                    $expr->orX(
                        $expr->in('act_c.district', ':userDistrictsIds'),
                        'act_c.isSuperuser != 1',
                        $expr->in('act_c.group', ':userGroupsIds'),
                        $expr->in('act_c.user', ':userFollowingIds'),
                        $expr->in('act_c.groupSection', ':userGroupSectionIds'),
                        ':user MEMBER OF act_c.users'
                    )
                )
            )
            ->setParameter('userDistrictsIds', empty($districtsIds) ? false : $districtsIds)
            ->setParameter('userGroupsIds', empty($activeGroups) ? false : $activeGroups)
            ->setParameter('userFollowingIds', empty($userFollowingIds) ? false : $userFollowingIds)
            ->setParameter('userGroupSectionIds', empty($sectionsIds) ? false : $sectionsIds)
            ->setParameter('user', $user);
    }

    /**
     * Find activities by Following the user.
     *
     * @param User $user
     * @param User $following
     * @param \DateTime $start
     * @param null $activityTypes
     * @return Query
     * @internal param int $followingId
     */
    public function getActivitiesByFollowingQuery(User $user, User $following, \DateTime $start = null, $activityTypes = null)
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();
        $districtsIds = $user->getDistrictsIds();
        $sectionsIds = $user->getGroupSectionsIds();
        $activeGroups = $this->getEntityManager()->getRepository('CivixCoreBundle:UserGroup')
            ->getActiveGroupIds($user);

        $query = $this->getActivitiesQueryBuilder($following, $start, $activityTypes)
            ->leftJoin('act.activityConditions', 'act_c2')
            ->andWhere(
                $expr->andX(
                    'act_c2.user = :following',
                    $expr->in('act.group', ':userGroupsIds'),
                    $expr->orX(
                        $expr->in('act_c.district', ':userDistrictsIds'),
                        'act_c.isSuperuser != 1',
                        $expr->in('act_c.group', ':userGroupsIds'),
                        $expr->in('act_c.groupSection', ':userGroupSectionIds'),
                        ':user MEMBER OF act_c.users'
                    )
                )
            )
            ->setParameter('following', $following->getId())
            ->setParameter('userDistrictsIds', empty($districtsIds) ? false : $districtsIds)
            ->setParameter('userGroupsIds', empty($activeGroups) ? false : $activeGroups)
            ->setParameter('userGroupSectionIds', empty($sectionsIds) ? false : $sectionsIds)
            ->setParameter('user', $user)
            ->getQuery();
        
        return $query;
    }

    protected function getActivitiesQueryBuilder(User $user, \DateTime $start = null, array $activityTypes = null)
    {
        $cases = [
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
        if ($activityTypes) {
            $cases = array_intersect_key($cases, array_flip($activityTypes));
        }
        $qb = $this->createQueryBuilder('act')
            ->distinct(true)
            ->select('act', 'act_r', 'g')
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
            ->leftJoin('act.activityConditions', 'act_c')
            ->leftJoin('act.activityRead', 'act_r', Query\Expr\Join::WITH, 'act_r.user = :user')
            ->setParameter(':user', $user)
            ->leftJoin('act.group', 'g')
            ->orderBy('zone', 'ASC') // order by priority zone
            ->addOrderBy('is_followed', 'ASC') // order by followed user
            ->addOrderBy('act.sentAt', 'DESC');
        if (isset($cases['poll'])) {
            $qb->addSelect('q', 'qa', 'qs')
                ->leftJoin('act.question', 'q')
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
            $qb->addSelect('p', 'pv', 'pos')
                ->leftJoin('act.post', 'p')
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
            $qb->addSelect('up', 'ups', 'ps')
                ->leftJoin('act.petition', 'up')
                ->leftJoin('up.signatures', 'ups', Query\Expr\Join::WITH, 'ups.user = :user')
                ->leftJoin('up.subscribers', 'ps', Query\Expr\Join::WITH, 'ps = :user');
        } else {
            $qb->andWhere('
                act NOT INSTANCE OF (
                    Civix\CoreBundle\Entity\Activities\UserPetition
                )
            ');
        }

        if ($start) {
            $qb->andWhere('act.sentAt > :start')
                ->setParameter('start', $start->format('Y-m-d H:i:s'));
        }
        
        return $qb;
    }

    /**
     * @param $id
     * @param User $user
     * @return Activity[]
     */
    public function findWithActivityReadByIdAndUser($id, User $user)
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'ar')
            ->leftJoin('a.activityRead', 'ar', Query\Expr\Join::WITH, 'ar.user = :user')
            ->setParameter(':user', $user)
            ->where($this->_em->getExpressionBuilder()->in('a.id', $id))
            ->getQuery()->getResult();
    }

    public function countPriorityActivitiesByUser(User $user, \DateTime $start = null)
    {
        $qb = $this->getActivitiesByUserQueryBuilder($user, $start);
        $query = $qb
            ->resetDQLParts(['select', 'orderBy'])
            ->select('COUNT(act)')
            ->andWhere('
            (act.expireAt > CURRENT_TIMESTAMP() OR act.expireAt IS NULL)
            AND (
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
                OR
                (
                    p.boosted = true AND
                    pv.id IS NULL AND 
                    (p.user != :user OR act_r.id IS NULL) AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\Post
                    )
                )
                OR
                (
                    up.boosted = true AND
                    act_r.id IS NULL AND 
                    ups.id IS NULL AND 
                    (up.user != :user OR act_r.id IS NULL) AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\UserPetition
                    )
                )
            )
            ') // prioritized
            ->getQuery();

        return (int)$query->getSingleScalarResult();
    }

    /**
     * @param Question $question
     * @param User $user
     * @return Activity[]
     */
    public function findByQuestionWithUserReadMark(Question $question, User $user)
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'ar')
            ->leftJoin('a.activityRead', 'ar', 'WITH', 'ar.user = :user')
            ->setParameter(':user', $user)
            ->andWhere('a.question = :question')
            ->setParameter(':question', $question)
            ->getQuery()
            ->getResult();
    }
}

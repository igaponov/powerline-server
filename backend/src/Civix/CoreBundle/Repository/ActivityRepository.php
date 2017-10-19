<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class ActivityRepository extends EntityRepository
{
    /**
     * @param \DateTime $start
     * @param User $user
     * @param bool $closed
     * @return mixed
     *
     * @deprecated
     */
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

    /** @noinspection MoreThanThreeArgumentsInspection */
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
    public function findActivitiesByUser(User $user, \DateTime $start, $offset, $limit): array
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

        /** @var array $activities */
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
            return $activityRead->getUser()->getId() === $user->getId();
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
     * Find activities by Following the user.
     *
     * @param User $following
     * @param $offset
     * @param $limit
     *
     * @return array
     *
     * @deprecated
     */
    public function findActivitiesByFollowing(User $following, $offset, $limit): array
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

    public function updateResponseCountQuestion(Question $question): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            'UPDATE activities
            SET responses_count = (
                SELECT (
                    (
                        SELECT COUNT(pa.id)
                        FROM poll_questions pq
                        LEFT JOIN poll_answers pa ON pq.id = pa.question_id
                        WHERE pq.id = activities.question_id
                    )
                    +
                    (
                        SELECT COUNT(pc.id)
                        FROM poll_questions pq
                        LEFT JOIN poll_comments pc ON pq.id = pc.question_id
                        WHERE pq.id = activities.question_id AND pc.user_id IS NOT NULL
                    )
                )
            )
            WHERE question_id = :question',
            [':question' => $question->getId()]
        );
    }

    public function updateResponseCountUserPetition(UserPetition $petition): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            'UPDATE activities 
            SET responses_count = (
                SELECT (
                    (
                        SELECT COUNT(ps.id)
                        FROM user_petitions p
                        LEFT JOIN user_petition_signatures ps ON p.id = ps.petition_id
                        WHERE p.id = activities.petition_id
                    )
                    +
                    (
                        SELECT COUNT(pc.id)
                        FROM user_petitions p
                        LEFT JOIN user_petition_comments pc ON p.id = pc.petition_id
                        WHERE p.id = activities.petition_id AND pc.user_id IS NOT NULL
                    )
                )
            )
            WHERE petition_id = :petition',
            [':petition' => $petition->getId()]
        );
    }

    public function updateResponseCountPost(Post $post): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            'UPDATE activities
            SET responses_count = (
                SELECT (
                    (
                        SELECT COUNT(pv.id)
                        FROM user_posts p
                        LEFT JOIN post_votes pv ON p.id = pv.post_id
                        WHERE p.id = activities.post_id
                    )
                    +
                    (
                        SELECT COUNT(pc.id)
                        FROM user_posts p
                        LEFT JOIN post_comments pc ON p.id = pc.post_id
                        WHERE p.id = activities.post_id AND pc.user_id IS NOT NULL
                    )
                )
            ),
            upvotes_count = (
                SELECT COUNT(pv.id)
                FROM post_votes pv
                WHERE pv.`option` = :up AND pv.post_id = activities.post_id
            ),
            downvotes_count = (
                SELECT COUNT(pv.id)
                FROM post_votes pv
                WHERE pv.`option` = :down AND pv.post_id = activities.post_id
            )
            WHERE post_id = :post',
            [
                ':post' => $post->getId(),
                ':up' => Post\Vote::OPTION_UPVOTE,
                ':down' => Post\Vote::OPTION_DOWNVOTE,
            ]
        );
    }

    /**
     * @param $groupId
     * @param int $maxResults
     * @return mixed
     *
     * @deprecated
     */
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

    /**
     * @param $representativeId
     * @param int $maxResults
     * @return mixed
     *
     * @deprecated
     */
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

    public function updateOwnerUser(User $owner): void
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

    public function updateOwnerGroup(Group $owner): void
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

    public function updateOwnerRepresentative(UserRepresentative $owner): void
    {
    }

    public function updateOwnerAdmin(Superuser $owner): void
    {
    }

    /**
     * @param $id
     * @param User $user
     * @return Activity[]
     */
    public function findWithActivityReadByIdAndUser($id, User $user): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('ar', 'u', 'g')
            ->leftJoin('a.user', 'u')
            ->leftJoin('a.group', 'g')
            ->leftJoin('a.activityRead', 'ar', Query\Expr\Join::WITH, 'ar.user = :user')
            ->setParameter(':user', $user)
            ->where($this->_em->getExpressionBuilder()->in('a.id', $id))
            ->getQuery()->getResult();
    }

    /**
     * @param Question $question
     * @param User $user
     * @return Activity[]
     */
    public function findByQuestionWithUserReadMark(Question $question, User $user): array
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

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
use Civix\CoreBundle\Entity\Micropetitions\Petition;
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
                    $expr->in('act_c.districtId', ':userDistrictsIds'),
                    'act_c.isSuperuser = 1',
                    $expr->in('act_c.groupId', ':userGroupsIds'),
                    $expr->in('act_c.userId', ':userFollowingIds'),
                    $expr->in('act_c.groupSectionId', ':userGroupSectionIds'),
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
                    $expr->in('act_c.districtId', ':userDistrictsIds'),
                    'act_c.isSuperuser = 1',
                    $expr->in('act_c.groupId', ':userGroupsIds'),
                    $expr->in('act_c.userId', ':userFollowingIds'),
                    $expr->in('act_c.groupSectionId', ':userGroupSectionIds'),
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
                    $expr->in('act_c.districtId', ':userDistrictsIds'),
                    'act_c.isSuperuser = 1',
                    $expr->in('act_c.groupId', ':userGroupsIds'),
                    $expr->in('act_c.userId', ':userFollowingIds'),
                    $expr->in('act_c.groupSectionId', ':userGroupSectionIds'),
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
            ->where('act_c.userId = :following')
            ->setParameter('following', $following->getId())
            ->orderBy('act.sentAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()->getResult()
        ;
    }

    public function updateResponseCountQuestion(Question $question)
    {
        $query = $this->getEntityManager()
            ->createQuery('UPDATE Civix\CoreBundle\Entity\Activities\Question a
                              SET a.responsesCount = :questions_count
                            WHERE a.questionId = :question');
        $query->setParameter('question', $question->getId())
            ->setParameter('questions_count', $question->getAnswersCount())
        ;

        return $query->execute();
    }

    public function updateLeaderNewsResponseCountQuestion(Question $question)
    {
        $count = $this->getEntityManager()
            ->createQuery('SELECT count(c) FROM CivixCoreBundle:Poll\Comment c WHERE c.question = :question')
            ->setParameter('question', $question)
            ->getSingleScalarResult()
        ;

        //representative news auto add new comment after creation
        --$count;

        $this->getEntityManager()
            ->createQuery('UPDATE Civix\CoreBundle\Entity\Activities\LeaderNews rn
                              SET rn.responsesCount = :rn_count
                            WHERE rn.questionId = :question')
            ->setParameter('question', $question->getId())
            ->setParameter('rn_count', $count)
            ->execute()
        ;
    }

    public function updateResponseCountUserPetition(UserPetition $petition)
    {
        $count = $this->getEntityManager()->createQueryBuilder()
            ->select('count(a)')
            ->from(UserPetition\Signature::class, 'a')
            ->where('a.petition = :petition')
            ->setParameter('petition', $petition)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $quorum = $petition->getQuorumCount();
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->update()
            ->from(\Civix\CoreBundle\Entity\Activities\UserPetition::class, 'a')
            ->set('a.responsesCount', $count)
            ->set('a.quorum', $quorum)
            ->where('a.petition = :petition')
            ->setParameter('petition', $petition);

        return $qb->getQuery()->execute();
    }

    public function updateResponseCountPost(Post $post)
    {
        $count = $this->getEntityManager()->createQueryBuilder()
            ->select('count(a)')
            ->from(UserPetition\Signature::class, 'a')
            ->where('a.petition = :petition')
            ->setParameter('petition', $post)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $quorum = $post->getQuorumCount();
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->update()
            ->from(\Civix\CoreBundle\Entity\Activities\UserPetition::class, 'a')
            ->set('a.responsesCount', $count)
            ->set('a.quorum', $quorum)
            ->where('a.petition = :petition')
            ->setParameter('petition', $post);

        return $qb->getQuery()->execute();
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
     * @param User      $user
     * @param \DateTime $start
     *
     * @return Query
     */
    public function getActivitiesByUserQuery(User $user, \DateTime $start = null)
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

        $query = $this->getActivitiesQueryBuilder($user, $start)
            ->andWhere(
                $expr->orX(
                    $expr->in('act_c.districtId', ':userDistrictsIds'),
                    'act_c.isSuperuser != 1',
                    $expr->in('act_c.groupId', ':userGroupsIds'),
                    $expr->in('act_c.userId', ':userFollowingIds'),
                    $expr->in('act_c.groupSectionId', ':userGroupSectionIds'),
                    ':user MEMBER OF act_c.users'
                )
            )
            ->setParameter('userDistrictsIds', empty($districtsIds) ? false : $districtsIds)
            ->setParameter('userGroupsIds', empty($activeGroups) ? false : $activeGroups)
            ->setParameter('userFollowingIds', empty($userFollowingIds) ? false : $userFollowingIds)
            ->setParameter('userGroupSectionIds', empty($sectionsIds) ? false : $sectionsIds)
            ->setParameter('user', $user)
            ->getQuery();
        
        return $query;
    }

    /**
     * Find activities by Following the user.
     *
     * @param User $user
     * @param User $following
     * @param \DateTime $start
     * @return Query
     * @internal param int $followingId
     */
    public function getActivitiesByFollowingQuery(User $user, User $following, \DateTime $start = null)
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();
        $districtsIds = $user->getDistrictsIds();
        $sectionsIds = $user->getGroupSectionsIds();
        $activeGroups = $this->getEntityManager()->getRepository('CivixCoreBundle:UserGroup')
            ->getActiveGroupIds($user);

        $query = $this->getActivitiesQueryBuilder($following, $start)
            ->andWhere('act_c.userId = :following')
            ->andWhere(
                $expr->orX(
                    $expr->in('act_c.districtId', ':userDistrictsIds'),
                    'act_c.isSuperuser != 1',
                    $expr->in('act_c.groupId', ':userGroupsIds'),
                    $expr->in('act_c.groupSectionId', ':userGroupSectionIds'),
                    ':user MEMBER OF act_c.users'
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

    protected function getActivitiesQueryBuilder(User $user, \DateTime $start = null)
    {
        $qb = $this->createQueryBuilder('act')
            ->distinct(true)
            ->select('act', 'act_r', 'p', 'up', 'ups', 'pv', 'q', 'qs', 'ps', 'pos')
            // 0 = Prioritized Zone (unread, unanswered)
            // 2 = Expired Zone (expired)
            // 1 = Non-Prioritized Zone (others)
            ->addSelect('
            (CASE WHEN act.expireAt < CURRENT_TIMESTAMP()
            THEN 2 
            WHEN 
                (
                    qa.id IS NULL AND 
                    act NOT INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\LeaderNews, 
                        Civix\CoreBundle\Entity\Activities\Petition
                    )
                )
                OR 
                (
                    act_r.id IS NULL AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\LeaderNews, 
                        Civix\CoreBundle\Entity\Activities\Petition
                    )
                )
                OR
                (
                    p.boosted = true AND
                    pv.id IS NULL AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\Post
                    )
                )
                OR
                (
                    up.boosted = true AND
                    act_r.id IS NULL AND 
                    ups.id IS NULL AND 
                    act INSTANCE OF (
                        Civix\CoreBundle\Entity\Activities\UserPetition
                    )
                )
            THEN 0
            ELSE 1
            END) AS zone')
            ->addSelect('CASE WHEN act_c.userId = :user THEN 0 ELSE 1 END AS HIDDEN is_followed')
            ->leftJoin('act.activityConditions', 'act_c')
            ->leftJoin('act.activityRead', 'act_r', Query\Expr\Join::WITH, 'act_r.user = :user')
            ->setParameter(':user', $user)
            ->leftJoin('act.question', 'q')
            ->leftJoin('act.petition', 'up')
            ->leftJoin('act.post', 'p')
            ->leftJoin('q.answers', 'qa', Query\Expr\Join::WITH, 'qa.user = :user')
            ->leftJoin('up.signatures', 'ups', Query\Expr\Join::WITH, 'ups.user = :user')
            ->leftJoin('p.votes', 'pv', Query\Expr\Join::WITH, 'pv.user = :user')
            ->leftJoin('up.subscribers', 'ps', Query\Expr\Join::WITH, 'ps = :user')
            ->leftJoin('p.subscribers', 'pos', Query\Expr\Join::WITH, 'pos = :user')
            ->leftJoin('q.subscribers', 'qs', Query\Expr\Join::WITH, 'qs = :user')
            ->orderBy('zone', 'ASC') // order by priority zone
            ->addOrderBy('is_followed', 'ASC') // order by followed user
            ->addOrderBy('act.sentAt', 'DESC');
        if ($start) {
            $qb->where('act.sentAt > :start')
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
}

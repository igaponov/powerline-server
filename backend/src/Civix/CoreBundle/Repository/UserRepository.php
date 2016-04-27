<?php

namespace Civix\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Service\PushSender;

/**
 * UserRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    /**
     * Get all users with filling address profile.
     *
     * @return array
     */
    public function getAllUsersWithAddressProfile()
    {
        return $this->createQueryBuilder('u')
                ->where('u.state IS NOT NULL')
                ->andWhere('u.city IS NOT NULL')
                ->andWhere('u.address1 IS NOT NULL')
                ->getQuery()
                ->getResult();
    }

    public function getQueryUserOrderedById()
    {
        return $this->getEntityManager()
                ->createQuery('SELECT u FROM CivixCoreBundle:User u ORDER BY u.id DESC');
    }

    public function removeUser(User $user)
    {
        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Activity a')
            ->set('a.user', 'NULL')
            ->where('a.user = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()
            ->createQueryBuilder()
            ->update('CivixCoreBundle:Poll\Answer a')
            ->set('a.user', 'NULL')
            ->where('a.user = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()->getConnection()
                ->delete('users_groups', array('user_id' => $user->getId()));

        $this->getEntityManager()
            ->createQueryBuilder()
            ->delete('CivixCoreBundle:User u')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->execute();
    }

    /**
     * @deprecated
     */
    public function follow(User $follower, User $user)
    {
        if (!$this->isFollower($user, $follower)) {
            $userFollow = $this->createUserFollow($user, $follower);
            $this->getEntityManager()->persist($userFollow);

            return $userFollow;
        }

        return false;
    }

    /**
     * @deprecated
     */
    public function unfollow(User $follower, User $user)
    {
        $userFollow = $this->isFollower($user, $follower);
        if ($userFollow) {
            $user->removeFollower($userFollow);
            $this->getEntityManager()->remove($userFollow);

            return true;
        }

        return false;
    }

    /**
     * @deprecated
     */
    public function active(User $user, User $follower)
    {
        $userFollow = $this->isFollower($user, $follower);

        if ($userFollow && $userFollow->getStatus() != \Civix\CoreBundle\Entity\UserFollow::STATUS_ACTIVE) {
            $userFollow->setStatus(\Civix\CoreBundle\Entity\UserFollow::STATUS_ACTIVE)
                    ->setDateApproval(new \DateTime());

            $this->getEntityManager()->persist($userFollow);

            return true;
        }

        return false;
    }

    /**
     * @deprecated
     */
    public function reject(User $user, User $follower)
    {
        $userFollow = $this->isFollower($user, $follower);
        if ($userFollow) {
            $user->removeFollowing($userFollow);
            $this->getEntityManager()->remove($userFollow);

            return true;
        }

        return false;
    }

    /**
     * @deprecated
     */
    public function isFollower(User $user, User $follower)
    {
        return $this->getEntityManager()->createQueryBuilder()
                ->select('f')
                ->from('CivixCoreBundle:UserFollow', 'f')
                ->where('f.user = :user')
                ->andWhere('f.follower = :follower')
                ->setParameter('user', $user)
                ->setParameter('follower', $follower)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function getUserByUsername(User $user, $username)
    {
        $followingIds = $user->getFollowingIds();

        return $this->createQueryBuilder('u')
                ->where('u.username = :username')
                ->andWhere('u.id <> :user')
                ->andWhere('u.id NOT IN (:ids)')
                ->setParameter('username', $username)
                ->setParameter('user', $user->getId())
                ->setParameter('ids', empty($followingIds) ? array(0) : $followingIds)
                ->getQuery()
                ->getResult();
    }

    public function getUsersByDistrictForPush($district, $type, $startId = 0, $limit = null)
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $query
            ->innerJoin('u.districts', 'dist');

        $this->setCommonFilterForPush($query, $expr);

        switch ($type) {
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $query->andWhere('u.isNotifMessages = true');
                break;
            case PushSender::TYPE_PUSH_ACTIVITY:
                $query->andWhere('u.isNotifQuestions = true');
                break;
            case PushSender::TYPE_PUSH_NEWS:
                $query->andWhere('u.isNotifDiscussions = true');
                break;
        }

        $query
            ->andWhere('dist.id = :districtId')
            ->andWhere('u.id > :startId')
            ->setParameter('districtId', $district)
            ->setParameter('startId', $startId)
            ->orderBy('u.id', 'ASC');

        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    public function getAllUsersForPush($startId, $limit)
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $this->setCommonFilterForPush($query, $expr);

        return $query
                ->andWhere('u.isNotifQuestions = true')
                ->andWhere('u.id > :startId')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults($limit)
                ->setParameter('startId', $startId)
                ->getQuery()
                ->getResult();
    }

    public function getUsersByFollowingForPush(User $user)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $expr = $query->expr();

        $query->select('u, g')
            ->from('CivixCoreBundle:User', 'u')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.following', 'f');

        $this->setCommonFilterForPush($query, $expr);

        return $query
            ->andWhere('f.user = :user')
            ->andWhere('u.isNotifMicroFollowing = true')
            ->andWhere('f.status = :status')
            ->setParameter(':user', $user)
            ->setParameter('status', UserFollow::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function getUsersByGroupForPush($groupId, $type, $startId = 0, $limit = null)
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $query->innerJoin('u.groups', 'gr');
        $this->setCommonFilterForPush($query, $expr);

        switch ($type) {
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $query->andWhere('u.isNotifMessages = true');
                break;
            case PushSender::TYPE_PUSH_PETITION:
                $query->andWhere('u.isNotifMicroGroup = true');
                break;
            case PushSender::TYPE_PUSH_ACTIVITY:
                $query->andWhere('u.isNotifQuestions = true');
                break;
        }

        $query
                ->andWhere('gr.group = :groupId')
                ->andWhere('u.id > :startId')
                ->orderBy('u.id', 'ASC')
                ->setParameter('groupId', $groupId)
                ->setParameter('startId', $startId);

        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    public function getUsersBySectionsForPush($sectionsIds, $type, $startId = 0, $limit = null)
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $query->innerJoin('u.groupSections', 'gs');
        $this->setCommonFilterForPush($query, $expr);
        switch ($type) {
            case PushSender::TYPE_PUSH_ANNOUNCEMENT:
                $query->andWhere('u.isNotifMessages = true');
                break;
            case PushSender::TYPE_PUSH_PETITION:
                $query->andWhere('u.isNotifMicroGroup = true');
                break;
            case PushSender::TYPE_PUSH_ACTIVITY:
                $query->andWhere('u.isNotifQuestions = true');
                break;
        }

        $query
                ->andWhere('gs.id in (:sections)')
                ->andWhere('u.id > :startId')
                ->orderBy('u.id', 'ASC')
                ->setParameter('sections', $sectionsIds)
                ->setParameter('startId', $startId);

        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    public function getUserForPush($userId)
    {
        /** @var $query \Doctrine\ORM\QueryBuilder */
        $query = $this->createQueryBuilder('u');
        $expr = $query->expr();

        $this->setCommonFilterForPush($query, $expr);

        return $query
                ->andWhere('u.isNotifMessages = true')
                ->andWhere('u.id = :userId')
                ->setParameter('userId', $userId)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function getUsersByEmails($emails)
    {
        return empty($emails) ?  array() : $this->createQueryBuilder('u')
                ->where('u.email in (:emails)')
                ->setParameter('emails', $emails)
                ->getQuery()
                ->getResult();
    }

    /**
     * @deprecated Use Notification\AndroidEndpoint instead (Amazon SNS integration)
     */
    public function removeDeviceTokens(User $user)
    {
        if (!$user->getIosDevice() && !$user->getAndroidDevice()) {
            return;
        }

        $qb = $this->getEntityManager()
            ->createQueryBuilder();
        $qb->update('CivixCoreBundle:User', 'u');

        if ($user->getIosDevice()) {
            $qb->set('u.iosDevice', 'NULL')
                ->where('u.iosDevice = :token')
                ->setParameter('token', $user->getIosDevice());
        } elseif ($user->getAndroidDevice()) {
            $qb->set('u.androidDevice', 'NULL')
                ->where('u.androidDevice = :token')
                ->setParameter('token', $user->getAndroidDevice());
        }

        return $qb->getQuery()->execute();
    }

    public function findByQueryForFollow($query, User $user)
    {
        $qb = $this->createQueryBuilder('u');
        $this->addQueryFilter($qb, $query);

        return $qb
            ->andWhere('u.id != :user_id')
            ->setParameter('user_id', $user->getId())
            ->getQuery()->getResult()
        ;
    }

    public function getUserByFacebookId($facebookId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.facebookId = :facebookId')
            ->setParameter('facebookId', $facebookId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getFacebookUsers(array $facebookIds, $exludeIds)
    {
        return $this->createQueryBuilder('u')
            ->where('u.facebookId IN (:facebookIds)')
            ->andWhere('u.id NOT IN (:following)')
            ->setParameter('facebookIds', empty($facebookIds) ? array(-1) : $facebookIds)
            ->setParameter('following', empty($exludeIds) ? array(0) : $exludeIds)
            ->getQuery()
            ->getResult();
    }

    public function findByParams($params, array $orderBy = null, $limit = null, $offset = null, User $user = null)
    {
        $qb = $this->createQueryBuilder('u');
        if (!empty($params['query'])) {
            $this->addQueryFilter($qb, $params['query']);
        }

        if (isset($params['unfollowing']) && $params['unfollowing'] && $user) {
            $this->addUnfollowingFilter($qb, $user);
        }

        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy($sort, $order);
        }

        return $qb
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()->getResult()
        ;
    }

    public function getUsersByGroup($groupId, $page, $limit)
    {
        $query = $this->createQueryBuilder('u');

        $query->innerJoin('u.groups', 'gr');

        $query
            ->orderBy('u.id', 'ASC')
            ->where('gr.group = :groupId')
            ->setParameter('groupId', $groupId);

        $query->setMaxResults($limit);
        $query->setFirstResult(($page - 1) * $limit);

        return $query->getQuery()->getResult();
    }

    private function addUnfollowingFilter(QueryBuilder $qb, User $user)
    {
        $excludedIds = array_merge(array($user->getId()), $user->getFollowingIds());
        $qb->andWhere('u.id NOT IN (:excluded_ids)')->setParameter('excluded_ids', $excludedIds);

        return $qb;
    }

    private function addQueryFilter(QueryBuilder $qb, $query)
    {
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('u.username', $qb->expr()->literal('%'.$query.'%')),
            $qb->expr()->like('u.firstName', $qb->expr()->literal('%'.$query.'%')),
            $qb->expr()->like('u.lastName', $qb->expr()->literal('%'.$query.'%'))
        ));

        return $qb;
    }

    private function createUserFollow(User $user, User $follower)
    {
        $followEntity = new \Civix\CoreBundle\Entity\UserFollow();
        $followEntity->setStatus(\Civix\CoreBundle\Entity\UserFollow::STATUS_PENDING)
            ->setDateCreate(new \DateTime())
            ->setFollower($follower)
            ->setUser($user);

        return $followEntity;
    }

    private function setCommonFilterForPush($query, $expr)
    {
        $curDate = new \DateTime();

        $query
            ->andWhere(
                $expr->orX(
                    'u.doNotDisturb = false',
                    $expr->andX(
                        'u.doNotDisturb = true',
                        'u.isNotifScheduled = true',
                        $expr->not(
                            $expr->between(
                                ':currentTime',
                                'u.scheduledFrom',
                                'u.scheduledTo'
                            )
                        )
                    )
                ))
            ->setParameter('currentTime', $curDate->format('H:i:s'));

        return $query;
    }

    public function getUsersByEmailAndPhoneHashes(
        array $emailHashes, 
        array $phoneHashes, 
        $exclude, 
        $page, 
        $limit
    )
    {
        $query = $this->createQueryBuilder('u');

        $query->orderBy('u.id', 'ASC');
        if ($exclude) {
            $query->where($query->expr()->neq('u.id', $exclude));
        }
        $or = $query->expr()->orX();
        if ($emailHashes) {
            $or->add($query->expr()->in('u.emailHash', $emailHashes));
        }
        if ($phoneHashes) {
            $or->add($query->expr()->in('u.phoneHash', $phoneHashes));
        }
        $query->andWhere($or);

        $query->setMaxResults($limit);
        $query->setFirstResult(($page - 1) * $limit);

        return $query->getQuery()->getResult();
    }

    /**
     * Returns users [{name, address}, ...]
     * 
     * @param $groupId
     * @return array
     */
    public function getUsersEmailsByGroup($groupId)
    {
        $query = $this->createQueryBuilder('u');
        $query
            ->select('partial u.{id,firstName,lastName,email}')
            ->innerJoin('u.groups', 'gr')
            ->orderBy('u.id', 'ASC')
            ->where('gr.group = :groupId')
            ->setParameter('groupId', $groupId);
        
        return $query->getQuery()->getResult(Query::HYDRATE_OBJECT);
    }
}
